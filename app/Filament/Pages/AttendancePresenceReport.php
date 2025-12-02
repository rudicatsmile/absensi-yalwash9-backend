<?php

namespace App\Filament\Pages;

use App\Models\Departemen;
use App\Models\ShiftKerja;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Validator;
use App\Filament\Widgets\AbsenceBreakdownChartWidget;
use App\Filament\Widgets\AttendancePresenceMatrixWidget;
use App\Filament\Widgets\PresenceChartWidget;
use App\Filament\Widgets\PresenceSummaryWidget;
use UnitEnum;
use BackedEnum;

class AttendancePresenceReport extends Page
{
    protected static ?string $navigationLabel = 'Laporan Kehadiran & Tidak Hadir';
    protected static ?string $title = 'Laporan Kehadiran & Tidak Hadir';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 11;

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $departemen_id = null;
    public ?int $shift_id = null;
    public ?string $status = 'semua';
    public ?string $mode = 'check';

    // Tidak menggunakan blade kustom; konten ditampilkan melalui widgets & actions

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();
        $this->departemen_id = (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) ? auth()->user()->departemen_id : null;
        $this->shift_id = null;
        $this->status = 'semua';
        $this->mode = 'check';
        $this->syncFiltersToSession();
    }

    protected function syncFiltersToSession(): void
    {
        session([
            'apr_start_date' => $this->start_date,
            'apr_end_date' => $this->end_date,
            'apr_departemen_id' => $this->departemen_id,
            'apr_shift_id' => $this->shift_id,
            'apr_status' => $this->status,
            'apr_mode' => $this->mode,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Filter')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->modalHeading('Filter Laporan')
                ->form([
                    DatePicker::make('start_date')->label('Tanggal Mulai')->native(false)->displayFormat('d-m-Y')->default($this->start_date),
                    DatePicker::make('end_date')->label('Tanggal Akhir')->native(false)->displayFormat('d-m-Y')->default($this->end_date),
                    Select::make('departemen_id')->label('Departemen')->options(function () {
                        $base = Departemen::query()->orderBy('name');
                        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
                            $base->whereKey(auth()->user()->departemen_id);
                        }
                        return [null => 'Semua Departemen'] + $base->pluck('name', 'id')->toArray();
                    })->searchable()->native(false)->default($this->departemen_id),
                    Select::make('shift_id')->label('Shift Kerja')->options(fn() => [null => 'Semua Shift'] + ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())->searchable()->native(false)->default($this->shift_id),
                    ToggleButtons::make('status')->label('Status')->options(['semua' => 'Semua', 'Hadir' => 'Hadir', 'Tidak Hadir' => 'Tidak Hadir'])->inline()->default($this->status),
                    ToggleButtons::make('mode')->label('Tampilan Sel')->options(['check' => 'Check', 'jumlah shift' => 'Jumlah Shift'])->inline()->default($this->mode),
                ])
                ->action(function (array $data): void {
                    $this->start_date = $data['start_date'] ?? $this->start_date;
                    $this->end_date = $data['end_date'] ?? $this->end_date;
                    $this->departemen_id = $data['departemen_id'] ?? $this->departemen_id;
                    $this->shift_id = $data['shift_id'] ?? $this->shift_id;
                    $this->status = $data['status'] ?? $this->status;
                    $this->mode = $data['mode'] ?? $this->mode;
                    $this->syncFiltersToSession();
                    $this->dispatch('refresh-widgets');
                }),

            Action::make('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => $this->buildApiUrl('xlsx'))
                ->openUrlInNewTab(),

            Action::make('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn() => $this->buildApiUrl('pdf'))
                ->openUrlInNewTab(),
        ];
    }

    protected function buildApiUrl(?string $export = null): string
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'departemen_id' => $this->departemen_id,
            'shift_id' => $this->shift_id,
            'status' => $this->status,
            'mode' => $this->mode,
        ];

        Validator::make($data, [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:semua,Hadir,Tidak Hadir'],
            'mode' => ['required', 'in:check,jumlah shift'],
        ])->validate();

        $query = http_build_query(array_filter($data, fn($v) => $v !== null && $v !== ''));
        $url = url('/api/reports/attendance-presence') . '?' . $query;
        return $url;
    }

    protected function getViewData(): array
    {
        $service = app(\App\Services\Reports\AttendancePresenceService::class);
        $result = $service->buildMatrix(
            $this->start_date,
            $this->end_date,
            $this->departemen_id,
            $this->shift_id,
            $this->status ?? 'semua',
            $this->mode ?? 'check'
        );

        return [
            'matrix' => $result,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PresenceSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AttendancePresenceMatrixWidget::class,
            PresenceChartWidget::class,
            AbsenceBreakdownChartWidget::class,

        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role !== 'employee';
    }
}
