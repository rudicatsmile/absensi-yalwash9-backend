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

    public function insertAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'shift_id' => 'required|integer|exists:shift_kerjas,id',
                'company_location_id' => 'required|integer|exists:company_locations,id',
                'departemen_id' => 'required|integer|exists:departemens,id',
                'date' => 'required|date_format:Y-m-d',
                'time_in' => 'nullable|date_format:H:i:s',
                'time_out' => 'nullable|date_format:H:i:s',
                'latlon_in' => 'nullable|string',
                'latlon_out' => 'nullable|string',
                'status' => 'required|string|in:on_time,late,absent,permit',
            ]);

            DB::beginTransaction();

            $attendance = \App\Models\Attendance::create($validated);

            DB::commit();

            Log::info('attendance.insert_success', [
                'user_id' => $validated['user_id'],
                'date' => $validated['date'],
                'actor_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Attendance inserted successfully',
                'data' => $attendance,
                'status' => 'success',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('attendance.insert_validation_failed', [
                'errors' => $e->errors(),
                'actor_id' => auth()->id(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'status' => 'error',
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('attendance.insert_failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'actor_id' => auth()->id(),
            ]);
            return response()->json([
                'message' => 'Failed to insert attendance',
                'status' => 'error',
            ], 500);
        }
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
            'attachment' => 'nullable|file|max:5048', // Max 5MB
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

            //Jika request permit_types = 4 (Izin Dinas)
            //Auto save table attendance
            if ($validated['permit_type_id'] === 4) {
                $attendanceData = [
                    'user_id' => $userId,
                    'shift_id' => $validated['shift_id'],
                    'company_location_id' => 1,    //Default Gedung A
                    'departemen_id' => $request->user()->departemen_id,
                    'date' => $validated['start_date'],
                    'time_in' => now()->setTimezone('Asia/Jakarta')->format('H:i:s'),
                    'time_out' => now()->setTimezone('Asia/Jakarta')->format('H:i:s'),
                    'latlon_in' => $this->getGeoLocation($request),
                    'latlon_out' => $this->getGeoLocation($request),
                    'status' => 'on_time',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                \App\Models\Attendance::create($attendanceData);

            }
        } else {

        }



        // Handle attachment upload if provided
        // if ($request->hasFile('attachment')) {
        //     $path = $request->file('attachment')->store('permit_attachments', 'public');
        //     $validated['attachment_url'] = $path;
        // }
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = '';

            // Check if file is an image to compress it
            if (str_starts_with($file->getMimeType(), 'image/')) {
                // Requires: composer require intervention/image
                // Uses Intervention Image v3
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($file);

                // Resize if width is larger than 1280px to save space
                if ($image->width() > 1280) {
                    $image->scale(width: 1280);
                }

                // Compress to JPEG with 75% quality
                $encoded = $image->toJpeg(quality: 75);

                $filename = $file->hashName();
                $path = 'permit_attachments/' . $filename;

                \Illuminate\Support\Facades\Storage::disk('public')->put($path, $encoded);
            } else {
                // Non-image files (PDF, etc.)
                $path = $file->store('permit_attachments', 'public');
            }

            $validated['attachment_url'] = $path;
        }

        $permit = Permit::create($validated);

        // Notification Logic
        try {
            // 1. Get departemen_id
            $dept_id = DB::scalar("SELECT departemen_id FROM users WHERE id = ?", [$userId]);

            if ($dept_id) {
                // 2. Get recipients (manager or kepala_sub_bagian in the same department)
                // Jika dept_id selain dari 7,8,9,10,11 maka kirim notif ke 515 dan 215
                // dept_id 7,8,9,10,11 : TK s/d SMK DP2

                $excludedDepts = [7, 8, 9, 10, 11];

                if (!in_array($dept_id, $excludedDepts)) {
                    $recipientIds = [515, 215];  //koordinator dan Rohayati
                } else {
                    $recipients = DB::select("SELECT id FROM users WHERE departemen_id = ? AND role IN ('manager', 'kepala_sub_bagian')", [$dept_id]);
                    $recipientIds = array_column($recipients, 'id');
                }

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

    /**
     * Mendapatkan lokasi GPS (Latitude, Longitude) dari request atau fallback.
     *
     * Fungsi ini mencoba mengambil koordinat dari input client.
     * Jika gagal (tidak didukung/izin ditolak/sinyal hilang), gunakan lokasi default perusahaan.
     *
     * @param Request $request
     * @return string Format "latitude,longitude"
     */
    private function getGeoLocation(Request $request)
    {
        // 1. Coba ambil dari input request (GPS perangkat)
        $latlon = $request->input('latlon_in');

        // 2. Validasi format latitude,longitude
        if ($latlon && preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $latlon)) {
            return $latlon;
        }

        // 3. Fallback: Gunakan lokasi default perusahaan (Gedung A / ID 1)
        // Ini menangani kasus: Perangkat tidak mendukung GPS, Izin lokasi ditolak, atau Sinyal GPS hilang
        try {
            $defaultLocation = \App\Models\CompanyLocation::find(1);
            if ($defaultLocation) {
                return "{$defaultLocation->latitude},{$defaultLocation->longitude}";
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        // 4. Default hardcoded jika database gagal
        return '-6.1914783,106.9372911';
    }
}
