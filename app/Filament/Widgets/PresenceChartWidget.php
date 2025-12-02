<?php

namespace App\Filament\Widgets;

use App\Services\Reports\AttendancePresenceService;
use Filament\Widgets\ChartWidget;

class PresenceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Kehadiran';
    protected int|string|array $columnSpan = '1';

    protected function getData(): array
    {
        $start = session('apr_start_date') ?? now()->toDateString();
        $end = session('apr_end_date') ?? now()->toDateString();
        $departemenId = session('apr_departemen_id');
        $shiftId = session('apr_shift_id');
        $status = session('apr_status') ?? 'semua';
        $mode = session('apr_mode') ?? 'check';

        $matrix = app(AttendancePresenceService::class)->buildMatrix($start, $end, $departemenId, $shiftId, $status, $mode);
        $t = $matrix['totals'] ?? ['present' => 0, 'absent_by_permit' => 0, 'absent_unexcused' => 0];

        return [
            'labels' => [
                'Hadir (' . (int) ($t['present'] ?? 0) . ')',
                'Izin (' . (int) ($t['absent_by_permit'] ?? 0) . ')',
                'Tidak Hadir (' . (int) ($t['absent_unexcused'] ?? 0) . ')',
            ],
            'datasets' => [
                [
                    'label' => 'Kehadiran',
                    'data' => [
                        (int) ($t['present'] ?? 0),
                        (int) ($t['absent_by_permit'] ?? 0),
                        (int) ($t['absent_unexcused'] ?? 0),
                    ],
                    'backgroundColor' => ['#22c55e', '#3b82f6', '#ef4444'],
                    'borderSkipped' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}

