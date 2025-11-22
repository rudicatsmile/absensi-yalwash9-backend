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

        if (! $user || ! in_array($user->role, ['employee','manager','kepala_sub_bagian'], true)) {
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
            'company-settings',
            'public-holidays',
            'weekends',
        ];

        if ($user->role === 'employee') {
            $blockedSlugs = array_merge($blockedSlugs, [
                'meeting-types',
                'religious-study-events',
            ]);
        }

        $segments = explode('/', $path);
        $resourceSlug = $segments[1] ?? null;

        if ($resourceSlug && in_array($resourceSlug, $blockedSlugs, true)) {
            \Log::info('audit:admin.menu.block', ['actor' => $user->id, 'slug' => $resourceSlug]);
            return $request->expectsJson()
                ? response()->json(['message' => 'Akses ditolak: Menu ini khusus admin'], 403)
                : response('Akses ditolak: Menu ini khusus admin', 403);
        }

        return $next($request);
    }
}