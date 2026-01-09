<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permit;
use App\Models\PermitType;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;

class PermitController extends Controller
{
    // Get all permit types
    public function getPermitTypes()
    {
        $types = PermitType::all();

        return response()->json([
            'message' => 'Permit types retrieved successfully',
            'data' => $types,
        ], 200);
    }

    // Get all permits for current user
    public function index_old(Request $request)
    {
        $userId = $request->user()->id;
        $status = $request->query('status');
        $shift_kerja_id = $request->query('shift_kerja_id');

        $query = Permit::where('employee_id', $userId)
            ->with(['permitType', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }

        $permits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Permits retrieved successfully',
            'status' => $status,
            'data' => $permits,
        ], 200);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $status = $request->query('status');
        $shift_kerja_id = $request->query('shift_kerja_id');

        $query = Permit::where('employee_id', $userId)
            ->with(['permitType', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }

        // Tambahkan filter jika shift_kerja_id tidak kosong
        if ($shift_kerja_id !== null && $shift_kerja_id !== '') {

            if (!is_numeric($shift_kerja_id)) {
                return response()->json([
                    'error' => 'Parameter shift_kerja_id harus berupa angka.',
                ], 422);
            }

            // $query->where('shift_id', $shift_kerja_id);

            $shiftIdInt = (int) $shift_kerja_id;

            //$shiftId = 5  : Absen Pengajian malam jumat
            //$shiftId != 5 : Absen Lainnya

            if ($shiftIdInt === 5) {
                $query->where('shift_id', 5);
            } else {
                $query->where('shift_id', '<>', 5);
            }

            /*
            $query->whereHas('employee', function ($q) use ($shift_kerja_id) {
                $q->whereHas('shiftKerjas', function ($qq) use ($shift_kerja_id) {
                    $qq->where('shift_kerjas.id', (int) $shift_kerja_id);
                });
            });
            */
        }

        $permits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Permits retrieved successfully',
            'data' => $permits,
        ], 200);
    }

    // Get permit by ID
    public function show($id)
    {
        $permit = Permit::with(['employee', 'permitType', 'approver'])->findOrFail($id);

        return response()->json([
            'message' => 'Permit retrieved successfully',
            'data' => $permit,
        ], 200);
    }

    // Create permit request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'permit_type_id' => 'required|exists:permit_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'attachment' => 'nullable|file|max:2048', // Max 2MB
        ]);

        $userId = $request->user()->id;

        // Calculate total workdays excluding weekends and holidays
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = WorkdayCalculator::countWorkdaysExcludingHolidays($startDate, $endDate);

        $validated['employee_id'] = $userId;
        $validated['total_days'] = $totalDays;
        $validated['status'] = 'pending';
        $validated['shift_id'] = $request->input('shift_kerja_id');

        $shiftIdInt = (int) $request->input('shift_kerja_id');

        //$shiftId = 5  : Absen Pengajian malam jumat
        //$shiftId != 5 : Absen Lainnya
        //Auto approve by sistem

        if ($shiftIdInt === 5) {
            $validated['status'] = 'approved';
            $validated['approved_by'] = 513;   //Name : Admin User (role : admin)
            $validated['approved_at'] = now();
        } else {

        }



        // Handle attachment upload if provided
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('permit_attachments', 'public');
            $validated['attachment_url'] = $path;
        }

        $permit = Permit::create($validated);

        // Notification Logic
        try {
            // 1. Get departemen_id
            $dept_id = DB::scalar("SELECT departemen_id FROM users WHERE id = ?", [$userId]);

            if ($dept_id) {
                // 2. Get recipients (manager or kepala_sub_bagian in the same department)
                $recipients = DB::select("SELECT id FROM users WHERE departemen_id = ? AND role IN ('manager', 'kepala_sub_bagian')", [$dept_id]);
                $recipientIds = array_column($recipients, 'id');

                // 3. Send notifications
                if (!empty($recipientIds)) {
                    $employeeName = $request->user()->name;
                    $permitTypeName = PermitType::where('id', $validated['permit_type_id'])->value('name') ?? 'Izin';
                    $startDateFormatted = Carbon::parse($validated['start_date'])->format('d/m/Y');

                    $title = 'Pengajuan Izin Baru';
                    $body = "{$employeeName} mengajukan izin {$permitTypeName} pada tanggal {$startDateFormatted}.";

                    $data = [
                        'type' => 'permit_created',
                        'permit_id' => (string) $permit->id,
                        'event_id' => (string) ($permit->shift_id ?? ''),
                    ];

                    $fcmService = app(FcmService::class);
                    foreach ($recipientIds as $recipientId) {
                        $fcmService->sendToUser($recipientId, $title, $body, $data);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send permit notification: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Permit request created successfully',
            'data' => $permit->load(['employee', 'permitType']),
        ], 201);
    }

    // Update permit request (only if pending)
    public function update(Request $request, $id)
    {
        $permit = Permit::findOrFail($id);

        // Only allow update if status is pending
        if ($permit->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update permit request that has been processed',
            ], 400);
        }

        // Only allow owner to update
        if ($permit->employee_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'permit_type_id' => 'sometimes|exists:permit_types,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'attachment_url' => 'nullable|string',
        ]);

        // Recalculate total days if dates changed
        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $startDate = Carbon::parse($validated['start_date'] ?? $permit->start_date);
            $endDate = Carbon::parse($validated['end_date'] ?? $permit->end_date);
            $validated['total_days'] = WorkdayCalculator::countWorkdaysExcludingHolidays($startDate, $endDate);
        }

        $permit->update($validated);

        return response()->json([
            'message' => 'Permit request updated successfully',
            'data' => $permit->load(['employee', 'permitType']),
        ], 200);
    }

    // Cancel permit request (only if pending)
    public function cancel($id, Request $request)
    {
        $permit = Permit::findOrFail($id);

        // Only allow cancel if status is pending
        if ($permit->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot cancel permit request that has been processed',
            ], 400);
        }

        // Only allow owner to cancel
        if ($permit->employee_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $permit->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Permit request cancelled successfully',
            'data' => $permit,
        ], 200);
    }

    public function approve($id)
    {
        try {
            DB::beginTransaction();

            $permit = Permit::findOrFail($id);

            if ($permit->status !== 'pending') {
                return response()->json([
                    'message' => 'Permit request has already been processed',
                ], 400);
            }

            // Recalculate total days to ensure consistency with holidays
            $totalDays = WorkdayCalculator::countWorkdaysExcludingHolidays(
                Carbon::parse($permit->start_date),
                Carbon::parse($permit->end_date)
            );

            // Update permit status
            $permit->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'total_days' => $totalDays,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Permit request approved successfully',
                'data' => $permit->load(['employee', 'permitType', 'approver']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to approve permit request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $permit = Permit::findOrFail($id);

        if ($permit->status !== 'pending') {
            return response()->json([
                'message' => 'Permit request has already been processed',
            ], 400);
        }

        $permit->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Permit request rejected successfully',
            'data' => $permit->load(['employee', 'permitType', 'approver']),
        ]);
    }
}
