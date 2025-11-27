<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->role !== 'employee') {
            return $next($request);
        }

        $path = $request->path();

        $allowedPatterns = [
            '/^api\/user$/',
            '/^api\/me$/',
            '/^api\/update-profile$/',
            '/^api\/api-user\/edit$/',
            '/^api\/checkin$/',
            '/^api\/checkout$/',
            '/^api\/is-checkin$/',
            '/^api\/api-attendances$/',
            '/^api\/dropdown\/company-locations$/',
            '/^api\/reports\/attendance$/',
            '/^api\/reports\/attendance-presence$/',
            '/^api\/leave-types$/',
            '/^api\/leave-balance$/',
            '/^api\/leaves$/',
            '/^api\/leaves\/\d+$/',
            '/^api\/leaves\/\d+\/cancel$/',
            '/^api\/permit-types$/',
            '/^api\/permit-balance$/',
            '/^api\/permits$/',
            '/^api\/permits\/\d+$/',
            '/^api\/permits\/\d+\/cancel$/',
            '/^api\/start-overtime$/',
            '/^api\/end-overtime$/',
            '/^api\/overtime-status$/',
            '/^api\/overtimes$/',
            '/^api\/update-fcm-token$/',
            '/^api\/users\/\d+\/push-tokens$/',
            '/^api\/events\/\d+\/confirm$/',
            '/^api\/api-user\/\d+$/',
            '/^api\/religious-study-events$/',
            '/^api\/religious-study-events\/detail$/',
        ];

        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                if (preg_match('/^api\/api-user\/(\d+)$/', $path, $m)) {
                    $requestedId = (int) ($m[1] ?? 0);
                    if ($requestedId !== (int) $user->id) {
                        return response(['message' => 'Forbidden'], 403);
                    }
                }
                return $next($request);
            }
        }

        return response(['message' => 'Forbidden'], 403);
    }
}
