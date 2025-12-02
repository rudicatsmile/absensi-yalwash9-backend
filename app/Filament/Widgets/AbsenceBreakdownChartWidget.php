<?php

namespace App\Filament\Widgets;

use App\Services\Reports\AttendancePresenceService;
use Filament\Widgets\ChartWidget;

class AbsenceBreakdownChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Rincian Ketidakhadiran';
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
        $t = $matrix['totals'] ?? ['absent_by_permit' => 0, 'absent_unexcused' => 0];

        return [
            'labels' => ['Berizin', 'Tanpa Keterangan'],
            'datasets' => [
                [
                    'data' => [
                        (int) ($t['absent_by_permit'] ?? 0),
                        (int) ($t['absent_unexcused'] ?? 0),
                    ],
                    'backgroundColor' => ['#3b82f6', '#f59e0b'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}

