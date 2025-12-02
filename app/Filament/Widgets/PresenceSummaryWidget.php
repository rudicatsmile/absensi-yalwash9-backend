<?php

namespace App\Filament\Widgets;

use App\Services\Reports\AttendancePresenceService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PresenceSummaryWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $start = session('apr_start_date') ?? now()->toDateString();
        $end = session('apr_end_date') ?? now()->toDateString();
        $departemenId = session('apr_departemen_id');
        $shiftId = session('apr_shift_id');
        $status = session('apr_status') ?? 'semua';
        $mode = session('apr_mode') ?? 'check';

        $service = app(AttendancePresenceService::class);
        $matrix = $service->buildMatrix($start, $end, $departemenId, $shiftId, $status, $mode);
        $totals = $matrix['totals'] ?? ['present' => 0, 'absent' => 0, 'absent_by_permit' => 0, 'absent_unexcused' => 0];

        $present = (int) ($totals['present'] ?? 0);
        $absentByPermit = (int) ($totals['absent_by_permit'] ?? 0);
        $absentUnexcused = (int) ($totals['absent_unexcused'] ?? 0);

        return [
            Stat::make('Hadir', $present)
                ->description('Jumlah hari hadir')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Izin', $absentByPermit)
                ->description('Jumlah hari berizin')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('info'),
            Stat::make('Tidak Hadir', $absentUnexcused)
                ->description('Jumlah hari tanpa keterangan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}

