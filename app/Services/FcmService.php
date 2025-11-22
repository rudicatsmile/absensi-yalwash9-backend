<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\AndroidNotification;

class FcmService
{
    /**
     * Kirim notifikasi ke banyak token sekaligus (multicast)
     *
     * @param array  $tokens  Daftar FCM token
     * @param string $title   Judul notifikasi
     * @param string $body    Isi notifikasi
     * @param array  $data    Data tambahan (akan masuk ke message.data di Flutter)
     * @param int    $retry   Jumlah retry jika ada kegagalan (default 1)
     * @return bool
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        int $retry = 1
    ): bool {
        $tokens = array_values(array_filter(array_unique($tokens)));

        if (empty($tokens)) {
            Log::info('FCM: Tidak ada token untuk dikirim');
            return true;
        }

        // Selalu masukkan title & body ke data agar mudah diakses di Flutter
        $data['title'] = $title;
        $data['body'] = $body;

        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::new()
                // === WAJIB: Notification payload (agar muncul saat app terminated) ===
                ->withNotification(FirebaseNotification::create($title, $body))

                // === Data custom kamu (tetap dikirim) ===
                ->withData(array_map(fn($value) => is_scalar($value) ? (string) $value : json_encode($value), $data))

                // === Android config – INI YANG BIKIN MUNCUL SAAT APP DITUTUP TOTAL ===
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',     // HARUS SAMA dengan di Flutter!
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // Agar klik buka app
                            'sound' => 'default',
                            'default_sound' => true,
                            'notification_priority' => 'PRIORITY_MAX',
                            'visibility' => 'PUBLIC',
                            'icon' => '@mipmap/ic_launcher',
                        ],
                    ])
                )

                // === iOS config (opsional, tapi bagus ada) ===
                ->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                            'category' => 'RELIGIOUS_EVENT', // jika pakai action button
                        ],
                    ],
                ]);

            $report = $messaging->sendMulticast($message, $tokens);

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            Log::info('FCM V1 Multicast Result', [
                'sent_to' => count($tokens),
                'success' => $successCount,
                'failed' => $failureCount,
            ]);

            // Retry untuk token yang gagal (maksimal 1x lagi)
            if ($failureCount > 0 && $retry > 0) {
                $failedTokens = collect($report->failures()->getItems())
                    ->map(fn($failure) => $failure->target()->value())
                    ->filter()
                    ->values()
                    ->toArray();

                Log::warning('FCM retry untuk token gagal', ['tokens' => $failedTokens]);

                return $this->sendToTokens($failedTokens, $title, $body, $data, $retry - 1);
            }

            return $failureCount === 0;

        } catch (\Throwable $e) {
            Log::error('FCM V1 gagal (akan coba legacy)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback ke Legacy HTTP API (jika masih punya server_key di config)
            return $this->sendLegacy($tokens, $title, $body, $data);
        }
    }

    /**
     * Fallback ke Legacy FCM API (HTTP v1 sudah deprecated, tapi masih jalan sampai 2026)
     */
    private function sendLegacy(array $tokens, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('firebase.server_key');

        if (empty($serverKey)) {
            Log::error('FCM Legacy: server_key tidak ada di config');
            return false;
        }

        $payload = [
            'registration_ids' => $tokens,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => 'high_importance_channel',
            ],
            'data' => $data,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            $ok = $response->successful();

            Log::info('FCM Legacy Result', [
                'status' => $response->status(),
                'success' => $ok,
                'body' => $response->body(),
            ]);

            return $ok;
        } catch (\Throwable $e) {
            Log::error('FCM Legacy Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Kirim ke satu token saja (convenience method)
     */
    public function sendToOne(string $token, string $title, string $body, array $data = []): bool
    {
        return $this->sendToTokens([$token], $title, $body, $data);
    }

    /**
     * Kirim silent notification (hanya data, tidak muncul notifikasi)
     * Cocok untuk update data real-time tanpa ganggu user
     */
    public function sendSilent(array $tokens, array $data): bool
    {
        $tokens = array_values(array_filter(array_unique($tokens)));
        if (empty($tokens))
            return true;

        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::new()
                ->withData(array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $data))
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                ]))
                ->withApnsConfig([
                    'headers' => ['apns-priority' => '10'],
                    'payload' => ['aps' => ['content-available' => 1]],
                ]);

            $messaging->sendMulticast($message, $tokens);
            return true;
        } catch (\Throwable $e) {
            Log::error('FCM Silent Error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
