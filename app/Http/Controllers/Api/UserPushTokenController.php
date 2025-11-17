<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePushTokenRequest;
use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPushTokenController extends Controller
{
    public function upsert(int $id, StorePushTokenRequest $request)
    {
        $user = User::find($id);
        if (!$user) {
            return response(['message' => 'User not found'], 404);
        }

        $token = trim($request->validated()['fcm_token']);
        $deviceInfo = $request->validated()['device_info'] ?? null;

        // Basic sanitization: collapse spaces
        $token = preg_replace('/\s+/', '', $token);

        // Additional format validation (printable ASCII)
        if (!preg_match('/^[\x20-\x7E]+$/', $token)) {
            return response(['message' => 'Invalid token format'], 422);
        }

        $hash = hash('sha256', $token);

        try {
            return DB::transaction(function () use ($user, $token, $hash, $deviceInfo) {
                $existing = UserPushToken::where('token_hash', $hash)->first();

                if (!$existing) {
                    $created = UserPushToken::create([
                        'user_id' => $user->id,
                        'token' => $token,
                        'token_hash' => $hash,
                        'device_info' => $deviceInfo,
                    ]);
                    Log::info('FCM token stored', [
                        'user_id' => $user->id,
                        'token_prefix' => substr($token, 0, 8),
                    ]);
                    return response(['message' => 'Token stored', 'data' => [
                        'id' => $created->id,
                    ]], 201);
                }

                if ($existing->user_id !== $user->id) {
                    Log::warning('FCM token reassigned to different user', [
                        'from_user_id' => $existing->user_id,
                        'to_user_id' => $user->id,
                        'token_prefix' => substr($token, 0, 8),
                    ]);
                }

                $existing->user_id = $user->id;
                $existing->device_info = $deviceInfo;
                $existing->save();

                Log::info('FCM token updated', [
                    'user_id' => $user->id,
                    'token_prefix' => substr($token, 0, 8),
                ]);

                return response(['message' => 'Token updated'], 200);
            });
        } catch (\Throwable $e) {
            Log::error('FCM token upsert failed', [
                'error' => $e->getMessage(),
            ]);
            return response(['message' => 'Database error'], 500);
        }
    }
}