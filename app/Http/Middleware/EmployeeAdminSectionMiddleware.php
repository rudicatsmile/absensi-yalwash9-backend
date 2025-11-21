<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAdminSectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'employee') {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (! str_starts_with($path, 'admin')) {
            return $next($request);
        }

        $blockedSlugs = [
            'work-shifts',
            'shift-kerjas',
            'departemens',
            'jabatans',
            'meeting-types',
            'religious-study-events',
            'company-settings',
            'public-holidays',
            'weekends',
        ];

        $segments = explode('/', $path);
        $resourceSlug = $segments[1] ?? null;

        if ($resourceSlug && in_array($resourceSlug, $blockedSlugs, true)) {
            return $request->expectsJson()
                ? response(['message' => 'Akses ditolak: Menu ini khusus admin'], 403)
                : response('Akses ditolak: Menu ini khusus admin', 403);
        }

        return $next($request);
    }
}