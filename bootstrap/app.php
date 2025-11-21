<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'employee.restrict' => \App\Http\Middleware\EmployeeAccessMiddleware::class,
            'employee.admin.block' => \App\Http\Middleware\EmployeeAdminSectionMiddleware::class,
        ]);

        $middleware->appendToGroup('api', [
            'employee.restrict',
        ]);

        $middleware->appendToGroup('web', [
            'employee.admin.block',
        ]);

        if (method_exists($middleware, 'appendToGroup')) {
            $middleware->appendToGroup('filament', [
                'employee.admin.block',
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
