<?php

namespace App\Providers;

use App\Models\LeaveType;
use App\Observers\LeaveTypeObserver;
use App\Models\Attendance;
use App\Observers\AttendanceObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Paginator::useBootstrapFive();

        // Register model observers
        LeaveType::observe(LeaveTypeObserver::class);
        Attendance::observe(AttendanceObserver::class);

        //Tambahan untuk Role - Permission
        /*
        mendefinisikan gate:
        - approve-high untuk admin|kepala_lembaga .
        - approve-subsection untuk admin|manager|kepala_sub_bagian dengan pembatasan departemen.
        - manage-users untuk admin|kepala_lembaga .

        */
        Gate::define('approve-high', fn($user) => in_array($user->role, ['admin', 'kepala_lembaga'], true));
        Gate::define('approve-subsection', function ($user, $record) {
            $dept = method_exists($record, 'employee') ? ($record->employee->departemen_id ?? null) : ($record->departemen_id ?? null);
            return in_array($user->role, ['admin', 'manager', 'kepala_sub_bagian'], true) && ($dept === $user->departemen_id);
        });
        Gate::define('manage-users', fn($user) => in_array($user->role, ['admin', 'kepala_lembaga'], true));
    }
}
