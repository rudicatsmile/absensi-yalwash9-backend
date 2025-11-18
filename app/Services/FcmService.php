<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FcmService
{
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], int $retry = 0): bool
    {
        $tokens = array_values(array_filter(array_unique($tokens)));
        if (empty($tokens)) {
            Log::info('FCM no tokens to send');
            return true;
        }

        try {
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create($title, $body);
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData(array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $data));
            $report = $messaging->sendMulticast($message, $tokens);
            $success = $report->successes()->count();
            $fail = $report->failures()->count();
            Log::info('FCM v1 send result', ['success' => $success, 'failures' => $fail]);
            if ($fail > 0 && $retry > 0) {
                $failedTokens = [];
                foreach ($report->failures()->getItems() as $failure) {
                    $failedTokens[] = $failure->target()->value();
                }
                return $this->sendToTokens($failedTokens, $title, $body, $data, $retry - 1);
            }
            return $fail === 0;
        } catch (\Throwable $e) {
            Log::warning('FCM v1 send unavailable, fallback to legacy', ['error' => $e->getMessage()]);
            $serverKey = config('firebase.server_key');
            if (empty($serverKey)) {
                Log::error('FCM server key missing');
                return false;
            }
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                ],
            ];
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key ' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', $payload);
                $ok = $response->successful();
                if (!$ok && $retry > 0) {
                    Log::warning('FCM legacy send failed, retrying', ['status' => $response->status(), 'body' => $response->body()]);
                    return $this->sendToTokens($tokens, $title, $body, $data, $retry - 1);
                }
                if (!$ok) {
                    Log::error('FCM legacy send error', ['status' => $response->status(), 'body' => $response->body()]);
                } else {
                    Log::info('FCM legacy send success', ['count' => count($tokens)]);
                }
                return $ok;
            } catch (\Throwable $ex) {
                Log::error('FCM legacy exception', ['error' => $ex->getMessage()]);
                return false;
            }
        }
    }
}