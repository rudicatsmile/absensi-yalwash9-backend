<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization', '');
        if (! str_starts_with($auth, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($auth, 7);
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        [$headB64, $payB64, $sigB64] = $parts;
        $headerJson = self::b64url_decode($headB64);
        $payloadJson = self::b64url_decode($payB64);
        if ($headerJson === false || $payloadJson === false) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (! is_array($header) || ! is_array($payload)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $secret = config('app.key');
        $expectedSig = hash_hmac('sha256', $headB64 . '.' . $payB64, $secret, true);
        $expectedSigB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');
        if (! hash_equals($expectedSigB64, $sigB64)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $exp = $payload['exp'] ?? null;
        if ($exp && time() >= (int) $exp) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        $sub = $payload['sub'] ?? null;
        if ($sub) {
            $user = User::find((int) $sub);
            if ($user) {
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }

    private static function b64url_decode(string $data): string|false
    {
        $replaced = strtr($data, '-_', '+/');
        $pad = strlen($replaced) % 4;
        if ($pad) {
            $replaced .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($replaced, true);
    }
}

