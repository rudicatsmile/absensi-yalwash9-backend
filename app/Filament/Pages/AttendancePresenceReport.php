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

    protected string $view = 'filament.pages.attendance-presence-report';

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();
        $this->departemen_id = null;
        $this->shift_id = null;
        $this->status = 'semua';
        $this->mode = 'check';
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            DatePicker::make('start_date')
                ->label('Tanggal Mulai')
                ->native(false)
                ->displayFormat('d-m-Y')
                ->default(now())
                ->live()
                ->closeOnDateSelection()
                ->afterStateUpdated(fn($state) => $this->start_date = $state),

            DatePicker::make('end_date')
                ->label('Tanggal Akhir')
                ->native(false)
                ->displayFormat('d-m-Y')
                ->default(now())
                ->live()
                ->closeOnDateSelection()
                ->afterStateUpdated(fn($state) => $this->end_date = $state),

            Select::make('departemen_id')
                ->label('Departemen')
                ->options(fn() => [null => 'Semua Departemen'] + Departemen::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->native(false)
                ->default($this->departemen_id)
                ->live()
                ->afterStateUpdated(fn($state) => $this->departemen_id = $state),

            Select::make('shift_id')
                ->label('Shift Kerja')
                ->options(fn() => [null => 'Semua Shift'] + ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->native(false)
                ->default($this->shift_id)
                ->live()
                ->afterStateUpdated(fn($state) => $this->shift_id = $state),

            ToggleButtons::make('status')
                ->label('Status')
                ->options([
                    'semua' => 'Semua',
                    'Hadir' => 'Hadir',
                    'Tidak Hadir' => 'Tidak Hadir',
                ])
                ->inline()
                ->default('semua')
                ->live()
                ->afterStateUpdated(fn($state) => $this->status = $state),

            ToggleButtons::make('mode')
                ->label('Tampilan Sel')
                ->options([
                    'check' => 'Check',
                    'jumlah shift' => 'Jumlah Shift',
                ])
                ->inline()
                ->default('check')
                ->live()
                ->afterStateUpdated(fn($state) => $this->mode = $state),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('Export CSV')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->url(fn() => $this->buildApiUrl('csv'))
            //     ->openUrlInNewTab(),
            // Action::make('Export Excel')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->color('success')
            //     ->url(fn() => $this->buildApiUrl('xlsx'))
            //     ->openUrlInNewTab(),
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
}
