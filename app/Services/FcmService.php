<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], int $retry = 0): bool
    {
        $serverKey = config('firebase.server_key');
        if (empty($serverKey)) {
            Log::error('FCM server key missing');
            return false;
        }

        $tokens = array_values(array_filter(array_unique($tokens)));
        if (empty($tokens)) {
            Log::info('FCM no tokens to send');
            return true;
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
                Log::warning('FCM send failed, retrying', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->sendToTokens($tokens, $title, $body, $data, $retry - 1);
            }

            if (!$ok) {
                Log::error('FCM send error', ['status' => $response->status(), 'body' => $response->body()]);
            } else {
                Log::info('FCM send success', ['count' => count($tokens)]);
            }
            return $ok;
        } catch (\Throwable $e) {
            Log::error('FCM exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}