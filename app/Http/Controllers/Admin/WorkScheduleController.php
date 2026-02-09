<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkSchedule;
use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{


    public function pdf(Request $request, $user_id)
    {
        try {
            // 1. Authorization
            if (!auth()->check()) {
                abort(403, 'Unauthorized');
            }
            // Permission check can be added here if needed, or handled via middleware
            // if (auth()->user()->cannot('download-work-schedule-pdf')) { abort(403); }

            // 2. Validation
            $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            if ($startDate->diffInDays($endDate) > 31) {
                return back()->with('error', 'Rentang maksimal 31 hari.');
            }

            // 4. Data Fetching
            $user = User::with('departemen')->findOrFail($user_id);

            $schedules = EmployeeWorkTimeSchedule::with('shift')
                ->where('user_id', $user_id)
                ->whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('schedule_date', 'asc')
                ->get();

            // 5. Data Grouping
            // Group by Shift -> then by Date
            // Since schedule_date is unique per day, grouping by date results in a collection of 1 item usually.
            // But the user requested "Setiap tanggal tersebut akan dibuat sebagai kelompok tabel terpisah".

            $groupedData = $schedules->groupBy(function ($item) {
                return $item->shift ? $item->shift->name : 'No Shift';
            })->map(function ($shiftGroup) {
                return $shiftGroup->groupBy(function ($item) {
                    return Carbon::parse($item->schedule_date)->format('d-m-Y');
                });
            });

            // 6. PDF Generation
            $data = [
                'groupedData' => $groupedData,
                'employee_name' => $user->name,
                'department_name' => $user->departemen->name ?? '-',
                'start_date' => $startDate->format('d-m-Y'),
                'end_date' => $endDate->format('d-m-Y'),
                'report_title' => 'Laporan Jadwal Kerja',
            ];

            $pdf = Pdf::loadView('pdf.work-schedule-report', $data);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream('jadwal-kerja-' . \Str::slug($user->name) . '-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    public function checkSchedule(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'shift_id' => 'required|exists:shift_kerjas,id',
            ]);

            $exists = EmployeeWorkSchedule::where('user_id', $validated['user_id'])
                ->where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->where('shift_id', $validated['shift_id'])
                ->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 400); // 400 Bad Request as per requirement
        } catch (\Exception $e) {
            Log::error('Error checking work schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi jadwal kerja. Silakan coba lagi.',
            ], 500);
        }
    }

    public function getSchedule(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'day' => 'required|integer|min:1|max:31',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'shift_id' => 'required|exists:shift_kerjas,id',
            ]);

            $date = Carbon::createFromDate($validated['year'], $validated['month'], $validated['day']);

            $schedules = EmployeeWorkTimeSchedule::where('user_id', $validated['user_id'])
                ->where('shift_id', $validated['shift_id'])
                ->whereDate('schedule_date', $date)
                ->pluck('jam_kerja_id')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $schedules,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error fetching work schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'shift_id' => [
                    'required',
                    'exists:shift_kerjas,id',
                    Rule::exists('shift_kerja_user', 'shift_kerja_id')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->user_id);
                    }),
                ],
                'day' => 'required|integer|min:1|max:31',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'jam_kerja_ids' => 'present|array', // Allow empty array to clear selection
                'jam_kerja_ids.*' => 'exists:jam_kerjas,id',
            ], [
                'shift_id.exists' => 'Shift yang dipilih tidak valid atau tidak ditugaskan ke pegawai ini.',
            ]);

            $date = Carbon::createFromDate($validated['year'], $validated['month'], $validated['day']);

            DB::transaction(function () use ($validated, $date) {
                // Delete existing records for this user and date AND specific shift
                EmployeeWorkTimeSchedule::where('user_id', $validated['user_id'])
                    ->where('shift_id', $validated['shift_id'])
                    ->whereDate('schedule_date', $date)
                    ->delete();

                if (!empty($validated['jam_kerja_ids'])) {
                    $jamKerjas = JamKerja::whereIn('id', $validated['jam_kerja_ids'])->get();

                    foreach ($jamKerjas as $jam) {
                        EmployeeWorkTimeSchedule::create([
                            'user_id' => $validated['user_id'],
                            'shift_id' => $validated['shift_id'],
                            'schedule_date' => $date,
                            'start_time' => $jam->start_time,
                            'end_time' => $jam->end_time,
                            'jam_kerja_id' => $jam->id,
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Data jadwal kerja berhasil disimpan',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving work schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
