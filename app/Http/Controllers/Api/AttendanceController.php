<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Models\ShiftKerja;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\EmployeeWorkSchedule;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    // checkin
    public function checkin(Request $request)
    {
        // validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $currentUser = $request->user();
        $currentDateTime = now();

        $scheduledShiftId = ShiftAssignment::query()
            ->forUser($currentUser->id)
            ->forDate($currentDateTime)
            ->scheduled()
            ->value('shift_id');

        //$resolvedShiftId = $scheduledShiftId ?? $currentUser->shift_kerja_id;
        $resolvedShiftId = $scheduledShiftId ?? $request->shift_kerja_id;
        $activeShift = $resolvedShiftId ? ShiftKerja::query()->find($resolvedShiftId) : null;

        $isWeekend = WorkdayCalculator::isWeekend($currentDateTime->copy());
        $isHoliday = WorkdayCalculator::isHoliday($currentDateTime->copy());

        $status = 'on_time';
        $lateMinutes = 0;


        if ($activeShift) {
            $startTimeString = $activeShift->getRawOriginal('start_time') ?? $activeShift->start_time?->format('H:i:s');


            if ($startTimeString) {
                $normalizedStartTime = strlen($startTimeString) === 5 ? $startTimeString . ':00' : $startTimeString;


                $shiftStart = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $currentDateTime->toDateString() . ' ' . $normalizedStartTime,
                    config('app.timezone')
                );

                if ($activeShift->is_cross_day && $currentDateTime->lessThan($shiftStart)) {
                    $shiftStart->subDay();
                }


                $graceMinutes = (int) ($activeShift->grace_period_minutes ?? 0);
                $lateThreshold = $shiftStart->copy()->addMinutes($graceMinutes);

                //echo 'normalizedStartTime : '. $normalizedStartTime .'\n';

                //echo $shiftStart.' ===> '.$currentDateTime. '>>>--> '.$lateThreshold;exit;

                if ($currentDateTime->greaterThan($lateThreshold)) {
                    $status = 'late';
                    $lateMinutes = (int) $lateThreshold->diffInMinutes($currentDateTime);
                }
            }
        }

        $attendance = new Attendance;
        $attendance->user_id = $currentUser->id;
        //$attendance->shift_id = $activeShift?->id;
        $attendance->shift_id = $request->shift_kerja_id;
        $attendance->company_location_id = $request->company_location_id;
        $attendance->departemen_id = $currentUser->departemen_id;
        $attendance->date = $currentDateTime->toDateString();
        $attendance->time_in = $currentDateTime->toTimeString();
        $attendance->latlon_in = $request->latitude . ',' . $request->longitude;
        $attendance->status = $status;
        $attendance->is_weekend = $isWeekend;
        $attendance->is_holiday = $isHoliday;
        $attendance->holiday_work = $activeShift ? ($isWeekend || $isHoliday) : false;
        $attendance->late_minutes = $lateMinutes;
        $attendance->save();

        return response([
            'message' => 'Checkin success',
            'attendance' => $attendance,
        ], 200);
    }

    // checkout
    public function checkout_old(Request $request)
    {
        // validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // get today attendance
        $today = now();

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', $today)
            ->first();

        // check if attendance not found
        if (!$attendance) {
            return response(['message' => 'Checkin first'], 400);
        }

        // save checkout
        $attendance->time_out = now()->toTimeString();
        $attendance->latlon_out = $request->latitude . ',' . $request->longitude;
        $attendance->save();

        return response([
            'message' => 'Checkout success',
            'attendance' => $attendance,
        ], 200);
    }

    public function checkout(Request $request)
    {
        // validate lat and long

        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'shift_kerja_id' => 'required|integer',
        ]);

        // get today attendance
        $today = now();
        $shiftKerjaId = (int) $request->input('shift_kerja_id');

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', $today)
            ->where('shift_id', $shiftKerjaId)
            ->first();

        // check if attendance not found
        if (!$attendance) {
            return response(['message' => 'Checkin first'], 400);
        }

        // save checkout
        $attendance->time_out = now()->toTimeString();
        $attendance->latlon_out = $request->latitude . ',' . $request->longitude;
        $attendance->save();

        return response([
            'message' => 'Checkout success',
            'attendance' => $attendance,
        ], 200);
    }

    // check is checkedin
    public function isCheckedin_old(Request $request)
    {
        // get today attendance
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', now())
            ->first();

        $isCheckout = $attendance ? $attendance->time_out : false;

        return response([
            'checkedin' => $attendance ? true : false,
            'checkedout' => $isCheckout ? true : false,
        ], 200);
    }

    public function isCheckedin_ok_as_backup_without_shiftId_5(Request $request)
    {
        $shiftKerjaId = $request->input('shiftKerjaId', $request->input('shift_kerja_id'));

        // get today attendance
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->when($shiftKerjaId !== null && $shiftKerjaId !== '', function ($query) use ($shiftKerjaId) {
                $query->where('shift_id', (int) $shiftKerjaId);
            })
            ->whereDate('date', now())
            ->first();

        $isCheckout = $attendance ? $attendance->time_out : false;

        return response([
            'checkedin' => $attendance ? true : false,
            'checkedout' => $isCheckout ? true : false,
        ], 200);
    }

    public function isCheckedin(Request $request)
    {
        $shiftKerjaId = $request->input('shiftKerjaId', $request->input('shift_kerja_id'));
        //echo  $shiftKerjaId.' ---';
        // get today attendance
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->when($shiftKerjaId !== null && $shiftKerjaId !== '', function ($query) use ($shiftKerjaId) {
                $shiftIdInt = (int) $shiftKerjaId;

                // if ($shiftIdInt === 5) {
                //     $query->where('shift_id', 5);
                // } else {
                //     $query->where('shift_id', '<>', 5);
                // }
                $query->where('shift_id', $shiftIdInt);
            })
            ->whereDate('date', now())
            ->first();

        $isCheckout = $attendance ? $attendance->time_out : false;

        //Cek apakah pegawai hari ini memiliki hak bekerja
        // Default true: jika data tidak ditemukan atau terjadi error, anggap pegawai boleh bekerja
        $allowedToWork = true;

        try {
            $today = now();
            // Ambil ID shift dari request atau dari profil pegawai
            $targetShiftId = $shiftKerjaId ? (int) $shiftKerjaId : $request->user()->shift_kerja_id;

            //var_dump("Controller Check:");
            //var_dump("User ID: " . $request->user()->id);
            //var_dump("Target Shift ID: " . $targetShiftId);
            //var_dump("Today Month: " . $today->month . ' :: '. $today->year);
            //var_dump("Today Year: " . $today->year);

            if ($targetShiftId) {
                // Log::info("Checking schedule for User: {$request->user()->id}, Shift: {$targetShiftId}, Month: {$today->month}, Year: {$today->year}");
                $schedule = EmployeeWorkSchedule::where('user_id', $request->user()->id)
                    ->where('shift_id', $targetShiftId)
                    ->where('month', $today->month)
                    ->where('year', $today->year)
                    ->first();

                if ($schedule) {
                    $currentDay = (string) $today->day;
                    // Cek apakah hari ini (allowed_day) bernilai true
                    if (isset($schedule->allowed_days[$currentDay])) {
                        // Jika ada setting untuk hari ini, ikuti setting tersebut
                        $allowedToWork = $schedule->allowed_days[$currentDay] === true;
                    }
                    // Jika tidak ada setting untuk hari ini, tetap true (default)
                    //var_dump("Allowed To Work: " . $allowedToWork) ;
                } else {
                    // Data tidak ditemukan, tetap true (default)
                    //var_dump("Not Allowed To Work: ") ;
                    Log::info("Schedule not found for User: {$request->user()->id}, Shift: {$targetShiftId}, Month: {$today->month}, Year: {$today->year}. Allowed to work by default.");
                }
            }
        } catch (\Exception $e) {
            // Jika terjadi error (koneksi db, dll), log error dan tetap perbolehkan bekerja (fail open)
            Log::error('Error checking work rights: ' . $e->getMessage());
            $allowedToWork = true;
        }
        //jika $allowedToWork false, maka $attendance = false dan $isCheckout = false
        if (!$allowedToWork) {
            $attendance = true;
            $isCheckout = true;
        }

        return response([
            'checkedin' => $attendance ? true : false,
            'checkedout' => $isCheckout ? true : false,
            'allowed_to_work' => $allowedToWork,
        ], 200);
    }

    // index
    public function index(Request $request)
    {
        $date = $request->input('date');

        $currentUser = $request->user();

        //echo '---'.$currentUser;exit;

        $query = Attendance::where('user_id', $currentUser->id);
        //echo $query->toSql();exit;
        if ($date) {
            $query->where('date', $date);
        }

        $attendance = $query->get();

        return response([
            'message' => 'Success',
            'data' => $attendance,
        ], 200);
    }
}
