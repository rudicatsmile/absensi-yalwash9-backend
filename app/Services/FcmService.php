<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\AndroidNotification;
use App\Jobs\SendFcmNotificationJob;

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
            $imageUrl = isset($data['image_path']) && is_string($data['image_path']) ? $data['image_path'] : '';
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
                            // Tampilkan gambar di tray notifikasi (BigPictureStyle)
                            ...($imageUrl ? ['image' => $imageUrl] : []),
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
                            // Agar rich media (gambar) bisa ditampilkan oleh Notification Service Extension
                            ...($imageUrl ? ['mutable-content' => 1] : []),
                        ],
                        ...($imageUrl ? ['image' => $imageUrl] : []),
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
     * Kirim notifikasi ke semua user dalam departemen tertentu
     */
    public function sendToDepartmentUsers(int $departemenId, string $title, string $body, array $data = [], int $retry = 1): bool
    {
        $tokens = \App\Models\UserPushToken::query()
            ->join('users', 'users.id', '=', 'user_push_tokens.user_id')
            ->where('users.departemen_id', $departemenId)
            ->pluck('user_push_tokens.token')
            ->all();
        return $this->sendToTokens($tokens, $title, $body, $data, $retry);
    }

    /**
     * Kirim notifikasi ke semua user dalam jabatan tertentu
     */
    public function sendToJabatanUsers(int $jabatanId, string $title, string $body, array $data = [], int $retry = 1): bool
    {
        $tokens = \App\Models\UserPushToken::query()
            ->join('users', 'users.id', '=', 'user_push_tokens.user_id')
            ->where('users.jabatan_id', $jabatanId)
            ->pluck('user_push_tokens.token')
            ->all();
        return $this->sendToTokens($tokens, $title, $body, $data, $retry);
    }

    //send to user by id
    public function sendToUser(int $userId, string $title, string $body, array $data = [], int $retry = 1): bool
    {
        $tokens = \App\Models\UserPushToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();
        return $this->sendToTokens($tokens, $title, $body, $data, $retry);
    }


    public function sendToJabatan($jabatanCode, $title, $body, $data = [])
    {
        // Normalisasi nama jabatan jadi topic (sama seperti di Flutter)
        // $topic = 'jabatan_' . strtolower(str_replace(' ', '_', $jabatanCode));
        $topic = "jabatan_" . strtolower($jabatanCode);


        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);

        Log::info("Notifikasi dikirim ke jabatan: $topic");
    }

    // Contoh: Kirim hanya ke Departemen tertentu (misal: HRD)
    public function sendToDepartment($departmentCode, $title, $body, $data = [])
    {
        $topic = "dept_" . strtolower($departmentCode); // dept_hrd

        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);

        Log::info("Notifikasi dikirim ke topic: $topic");
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

        $imageUrl = isset($data['image_path']) && is_string($data['image_path']) ? $data['image_path'] : '';
        $payload = [
            'registration_ids' => $tokens,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => 'high_importance_channel',
                // Tampilkan gambar di tray notifikasi jika tersedia
                ...($imageUrl ? ['image' => $imageUrl] : []),
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


    /**
     * Mengirim notifikasi ke pengguna tertentu secara asinkron melalui queue.
     *
     * Method ini mengambil ID pengguna, mencari semua token FCM yang valid,
     * lalu memasukkan tugas pengiriman notifikasi ke dalam antrian (queue).
     * Ini memastikan proses pengiriman tidak memblokir response aplikasi.
     *
     * @param int    $userId  ID dari pengguna yang akan dikirimi notifikasi.
     * @param string $title   Judul notifikasi.
     * @param string $body    Isi pesan notifikasi.
     * @param array  $data    Data tambahan yang akan dikirim bersama notifikasi.
     * @return void
     *
     * @example
     * // Di dalam controller atau service lain:
     * app(FcmService::class)->sendToUserWithJob(
     *     123, // User ID
     *     'Persetujuan Izin',
     *     'Pengajuan izin Anda telah disetujui.',
     *     ['permit_id' => 'P-001', 'type' => 'permit_status']
     * );
     */
    // public function sendToUserWithJob(int $userId, string $title, string $body, array $data = []): void
    // {
    //     // Ambil semua token FCM yang aktif milik pengguna
    //     $tokens = \App\Models\UserPushToken::query()
    //         ->where('user_id', $userId)
    //         ->pluck('token')
    //         ->filter()
    //         ->unique()
    //         ->values()
    //         ->all();

    //     if (empty($tokens)) {
    //         Log::info('FCM: Tidak ada token yang ditemukan untuk user', ['userId' => $userId]);
    //         return;
    //     }

    //     Log::info('FCM: Menambahkan job ke queue untuk user', [
    //         'userId' => $userId,
    //         'tokens_count' => count($tokens),
    //         'title' => $title
    //     ]);

    //     // Kirim ke queue untuk diproses secara asynchronous
    //     SendFcmNotificationJob::dispatch($tokens, $title, $body, $data);
    // }
}
