<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkScheduleController extends Controller
{
    public function getSchedule(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'day' => 'required|integer|min:1|max:31',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
            ]);

            $date = Carbon::createFromDate($validated['year'], $validated['month'], $validated['day']);

            $schedules = EmployeeWorkTimeSchedule::where('user_id', $validated['user_id'])
                ->whereDate('schedule_date', $date)
                ->pluck('jam_kerja_id')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $schedules,
            ]);

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
                'day' => 'required|integer|min:1|max:31',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'jam_kerja_ids' => 'present|array', // Allow empty array to clear selection
                'jam_kerja_ids.*' => 'exists:jam_kerjas,id',
            ]);

            $date = Carbon::createFromDate($validated['year'], $validated['month'], $validated['day']);

            DB::transaction(function () use ($validated, $date) {
                // Delete existing records for this user and date
                EmployeeWorkTimeSchedule::where('user_id', $validated['user_id'])
                    ->whereDate('schedule_date', $date)
                    ->delete();

                if (!empty($validated['jam_kerja_ids'])) {
                    $jamKerjas = JamKerja::whereIn('id', $validated['jam_kerja_ids'])->get();

                    foreach ($jamKerjas as $jam) {
                        EmployeeWorkTimeSchedule::create([
                            'user_id' => $validated['user_id'],
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
