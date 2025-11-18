<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\UserPushToken;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        try {
            $tokens = UserPushToken::where('user_id', $attendance->user_id)->pluck('token')->all();
            $title = 'Absensi Masuk';
            $body = 'Check-in tercatat pada ' . $attendance->date . ' ' . ($attendance->time_in ?? '');
            $data = [
                'type' => 'attendance_checkin',
                'attendanceId' => (string) $attendance->id,
                'status' => $attendance->status ?? 'on_time',
            ];
            (new FcmService())->sendToTokens($tokens, $title, $body, $data, 1);
        } catch (\Throwable $e) {
            Log::error('AttendanceObserver created push error', ['error' => $e->getMessage()]);
        }
    }

    public function updated(Attendance $attendance): void
    {
        try {
            if ($attendance->isDirty('time_out') && !empty($attendance->time_out)) {
                $tokens = UserPushToken::where('user_id', $attendance->user_id)->pluck('token')->all();
                $title = 'Absensi Keluar';
                $body = 'Check-out tercatat pada ' . $attendance->date . ' ' . ($attendance->time_out ?? '');
                $data = [
                    'type' => 'attendance_checkout',
                    'attendanceId' => (string) $attendance->id,
                    'status' => $attendance->status ?? 'on_time',
                ];
                (new FcmService())->sendToTokens($tokens, $title, $body, $data, 1);
            }
        } catch (\Throwable $e) {
            Log::error('AttendanceObserver updated push error', ['error' => $e->getMessage()]);
        }
    }
}