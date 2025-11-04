<?php

namespace App\Filament\Pages;

use App\Models\ShiftKerja;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Validator;
use UnitEnum;
use BackedEnum;

class AttendanceReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Laporan Kehadiran';
    protected static ?string $title = 'Laporan Kehadiran';
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 10;

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $shift_id = null;

    protected string $view = 'filament.pages.attendance-report';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->endOfMonth()->toDateString();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            DatePicker::make('start_date')
                ->label('Tanggal Awal')
                ->required()
                ->native(false)
                ->displayFormat('Y-m-d')
                ->default($this->start_date)
                ->live()
                ->afterStateUpdated(fn ($state) => $this->start_date = $state),

            DatePicker::make('end_date')
                ->label('Tanggal Akhir')
                ->required()
                ->native(false)
                ->displayFormat('Y-m-d')
                ->default($this->end_date)
                ->live()
                ->afterStateUpdated(fn ($state) => $this->end_date = $state),

            Select::make('shift_id')
                ->label('Shift')
                ->placeholder('Semua Shift')
                ->options(fn () => ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->native(false)
                ->default($this->shift_id)
                ->live()
                ->afterStateUpdated(fn ($state) => $this->shift_id = $state),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation(false)
                ->url(fn () => $this->buildExportUrl('csv'))
                ->openUrlInNewTab(),

            Action::make('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation(false)
                ->url(fn () => $this->buildExportUrl('xlsx'))
                ->openUrlInNewTab(),
        ];
    }

    protected function buildExportUrl(string $format): string
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'shift_id' => $this->shift_id,
            'export' => $format,
        ];

        // Validasi ringan sebelum ekspor
        Validator::make($data, [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'shift_id' => ['nullable', 'integer'],
            'export' => ['required', 'in:csv,xlsx'],
        ])->validate();

        $query = http_build_query(array_filter($data, fn ($v) => $v !== null && $v !== ''));

        return url('/api/reports/attendance') . '?' . $query;
    }

    protected function getViewData(): array
    {
        $query = $this->getAttendanceQuery()->with(['user:id,name', 'shift:id,name']);
        $attendances = $query->orderBy('date')->orderBy('user_id')->get();

        return [
            'attendances' => $attendances,
            'summary' => $this->summarize($attendances),
        ];
    }

    protected function getAttendanceQuery(): Builder
    {
        $start = $this->start_date ?? now()->startOfMonth()->toDateString();
        $end = $this->end_date ?? now()->endOfMonth()->toDateString();

        return Attendance::query()
            ->whereBetween('date', [$start, $end])
            ->when($this->shift_id, fn (Builder $q) => $q->where('shift_id', $this->shift_id));
    }

    protected function summarize(Collection $attendances): array
    {
        $total = $attendances->count();
        $users = $attendances->pluck('user_id')->unique()->count();
        $days = $attendances->pluck('date')->unique()->count();

        return [
            'total_records' => $total,
            'total_users' => $users,
            'days_count' => $days,
            'late_count' => $attendances->where('late_minutes', '>', 0)->count(),
            'early_leave_count' => $attendances->where('early_leave_minutes', '>', 0)->count(),
            'holiday_work_count' => $attendances->where('holiday_work', true)->count(),
        ];
    }
}