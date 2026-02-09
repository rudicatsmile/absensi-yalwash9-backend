<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkSchedule;
use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
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
