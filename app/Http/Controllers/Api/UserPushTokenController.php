<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertPushTokensRequest;
use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPushTokenController extends Controller
{
    public function upsert(int $id, UpsertPushTokensRequest $request)
    {
        $user = User::find($id);
        if (!$user) {
            return response(['message' => 'User not found'], 404);
        }
        $validated = $request->validated();
        $items = [];
        if (!empty($validated['tokens']) && is_array($validated['tokens'])) {
            $items = $validated['tokens'];
        } elseif (!empty($validated['fcm_token'])) {
            $items = [[
                'fcm_token' => $validated['fcm_token'],
                'device_info' => $validated['device_info'] ?? null,
            ]];
        } else {
            return response(['message' => 'No token provided'], 422);
        }

        $result = [
            'stored' => 0,
            'updated' => 0,
            'reassigned' => 0,
            'invalid' => 0,
        ];

        try {
            DB::transaction(function () use ($user, $items, &$result) {
                foreach ($items as $item) {
                    $raw = trim($item['fcm_token']);
                    $raw = preg_replace('/\s+/', '', $raw);
                    if (!preg_match('/^[\x20-\x7E]+$/', $raw)) {
                        $result['invalid']++;
                        continue;
                    }
                    $hash = hash('sha256', $raw);
                    $existing = UserPushToken::where('token_hash', $hash)->first();
                    if (!$existing) {
                        $created = UserPushToken::create([
                            'user_id' => $user->id,
                            'token' => $raw,
                            'token_hash' => $hash,
                            'device_info' => $item['device_info'] ?? null,
                        ]);
                        Log::info('FCM token stored', [
                            'user_id' => $user->id,
                            'token_prefix' => substr($raw, 0, 8),
                            'id' => $created->id,
                        ]);
                        $result['stored']++;
                        continue;
                    }
                    if ($existing->user_id !== $user->id) {
                        Log::warning('FCM token reassigned to different user', [
                            'from_user_id' => $existing->user_id,
                            'to_user_id' => $user->id,
                            'token_prefix' => substr($raw, 0, 8),
                        ]);
                        $result['reassigned']++;
                    }
                    $existing->user_id = $user->id;
                    $existing->device_info = $item['device_info'] ?? null;
                    $existing->save();
                    Log::info('FCM token updated', [
                        'user_id' => $user->id,
                        'token_prefix' => substr($raw, 0, 8),
                    ]);
                    $result['updated']++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('FCM token upsert failed', [
                'error' => $e->getMessage(),
            ]);
            return response(['message' => 'Database error'], 500);
        }

        return response(['message' => 'OK', 'result' => $result], 200);
    }
}