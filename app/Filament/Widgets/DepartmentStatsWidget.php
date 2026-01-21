<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepartmentStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '15s';

    protected static ?array $grid = [
        'default' => 1,
        'sm' => 2,
        'md' => 3,
        'xl' => 4,
    ];

    protected function getSelectedDate(): Carbon
    {
        $dateString = session('dashboard_filter_date') ?? today()->format('Y-m-d');
        return Carbon::parse($dateString);
    }

    protected function getSelectedShift(): ?string
    {
        return session('dashboard_filter_shift');
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        $role = strtolower($user->role);
        return in_array($role, ['kepala_lembaga', 'admin']);
    }

    protected function getStats(): array
    {
        $selectedDate = $this->getSelectedDate();
        $selectedShift = $this->getSelectedShift();

        $query = "
            SELECT
                d.id,
                d.name,
                COUNT(u.id) AS total_users,
                (
                    SELECT COUNT(*)
                    FROM attendances a
                    JOIN users u2 ON a.user_id = u2.id
                    WHERE u2.departemen_id = d.id
                    AND DATE(a.date) = ? " . ($selectedShift ? " AND a.shift_id = ?" : "") . "
                ) AS attendance_count
            FROM
                departemens d
            LEFT JOIN
                users u ON d.id = u.departemen_id
            GROUP BY
                d.id, d.name
            ORDER BY
                d.id
        ";

        $bindings = [$selectedDate->toDateString()];
        if ($selectedShift) {
            $bindings[] = $selectedShift;
        }

        $departmentStats = DB::select($query, $bindings);

        if (empty($departmentStats)) {
            return [
                Stat::make('Data Departemen', '0')
                    ->description('Tidak ada data ditemukan')
                    ->color('gray'),
            ];
        }

        return array_map(function ($deptStat) {
            $hadir = $deptStat->attendance_count;
            $total = $deptStat->total_users;
            $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;

            $color = $persen >= 90 ? 'success' : ($persen >= 70 ? 'warning' : 'danger');

            return Stat::make($deptStat->name, "$hadir / $total")
                ->description("$persen% hadir")
                ->descriptionIcon($persen >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($color);
        }, $departmentStats);
    }
}
