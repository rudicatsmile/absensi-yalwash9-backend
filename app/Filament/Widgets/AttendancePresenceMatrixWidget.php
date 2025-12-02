<?php

namespace App\Filament\Widgets;

use App\Models\Permit;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AttendancePresenceMatrixWidget extends BaseWidget
{
    protected static ?string $heading = 'Matriks Kehadiran per Tanggal';
    protected int|string|array $columnSpan = 'full';

    protected array $dates = [];
    protected array $attendIndex = [];
    protected array $permitIndex = [];

    protected function prepareMatrixState(): void
    {
        $start = session('apr_start_date') ?? now()->toDateString();
        $end = session('apr_end_date') ?? now()->toDateString();
        $shiftId = session('apr_shift_id');

        $period = CarbonPeriod::create($start, $end);
        $this->dates = collect($period)->map(fn($d) => $d->toDateString())->values()->all();

        $attendances = Attendance::query()
            ->select(['user_id', 'date'])
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy(fn($a) => $a->user_id . '|' . $a->date);

        $this->attendIndex = $attendances->all();

        $permits = Permit::query()
            ->select(['employee_id', 'permit_type_id', 'start_date', 'end_date', 'status'])
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $start)
            ->whereDate('start_date', '<=', $end)
            ->get();

        $index = [];
        foreach ($permits as $p) {
            foreach ($this->dates as $d) {
                if ($d >= $p->start_date->toDateString() && $d <= $p->end_date->toDateString()) {
                    $index[$p->employee_id][$d] = [
                        'permit_type_id' => $p->permit_type_id,
                    ];
                }
            }
        }
        $this->permitIndex = $index;
    }

    public function table(Table $table): Table
    {
        $this->prepareMatrixState();

        $departemenId = session('apr_departemen_id');
        $status = session('apr_status') ?? 'semua';

        $columns = [
            TextColumn::make('number')->label('No')->rowIndex(),
            TextColumn::make('name')->label('Nama')->state(fn(User $r) => $r->name)->searchable(),
        ];

        foreach ($this->dates as $d) {
            $label = Carbon::parse($d)->format('d-m-Y');
            $columns[] = TextColumn::make('date_' . $d)
                ->label($label)
                ->state(function (User $record) use ($d) {
                    $key = $record->id . '|' . $d;
                    $presentCount = isset($this->attendIndex[$key]) ? count($this->attendIndex[$key]) : 0;
                    $permitTypeId = $this->permitIndex[$record->id][$d]['permit_type_id'] ?? null;
                    if ($presentCount > 0) {
                        return '✔';
                    }
                    if ($permitTypeId) {
                        return 'ℹ';
                    }
                    return '✖';
                })
                ->alignCenter();
        }

        return $table
            ->query(
                User::query()
                    ->select(['id', 'name', 'departemen_id'])
                    ->when($departemenId, fn($q) => $q->where('departemen_id', $departemenId))
            )
            ->columns($columns)
            ->paginated(false);
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}

