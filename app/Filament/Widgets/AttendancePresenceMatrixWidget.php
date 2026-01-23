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
use Filament\Tables\Columns\Summarizers\Summarizer;

class AttendancePresenceMatrixWidget extends BaseWidget
{
    protected static ?string $heading = 'Matriks Kehadiran per Tanggal';
    protected int|string|array $columnSpan = 'full';

    protected array $dates = [];
    protected array $attendIndex = [];
    protected array $permitIndex = [];
    protected ?array $filteredUserIds = null;

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
        $mode = session('apr_mode') ?? 'check';

        $columns = [
            TextColumn::make('number')
                ->label('No')
                ->rowIndex(),
            TextColumn::make('name')
                ->label('Nama')
                ->state(fn(User $r) => $r->name)
                ->searchable()
                ->summarize(
                    $mode === 'jumlah shift'
                    ? Summarizer::make()
                        // ->label('Grand Total')
                        ->using(fn() => 'Grand Total')
                    : []
                ),
        ];

        foreach ($this->dates as $d) {
            $label = Carbon::parse($d)->format('d-m-Y');
            $column = TextColumn::make('date_' . $d)
                ->label($label)
                ->state(function (User $record) use ($d, $mode) {
                    $key = $record->id . '|' . $d;
                    $presentCount = isset($this->attendIndex[$key]) ? count($this->attendIndex[$key]) : 0;
                    $permitTypeId = $this->permitIndex[$record->id][$d]['permit_type_id'] ?? null;

                    if ($mode === 'jumlah shift') {
                        return $presentCount;
                    }

                    if ($presentCount > 0) {
                        return '✔';
                    }
                    if ($permitTypeId) {
                        return 'ℹ';
                    }
                    return '✖';
                })
                ->alignCenter();

            if ($mode === 'jumlah shift') {
                $column->summarize(
                    Summarizer::make()
                        ->label('')
                        ->using(function ($query) use ($d) {
                            if ($this->filteredUserIds === null) {
                                $this->filteredUserIds = $query->pluck('users.id')->toArray();
                            }
                            $sum = 0;
                            foreach ($this->filteredUserIds as $uid) {
                                $key = $uid . '|' . $d;
                                if (isset($this->attendIndex[$key])) {
                                    $sum += count($this->attendIndex[$key]);
                                }
                            }
                            return $sum;
                        })
                );
            }
            $columns[] = $column;
        }

        if ($mode === 'jumlah shift') {
            $columns[] = TextColumn::make('total_shifts')
                ->label('Total')
                ->state(function (User $record) {
                    $total = 0;
                    foreach ($this->dates as $d) {
                        $key = $record->id . '|' . $d;
                        if (isset($this->attendIndex[$key])) {
                            $total += count($this->attendIndex[$key]);
                        }
                    }
                    return $total;
                })
                ->alignCenter()
                ->summarize(
                    Summarizer::make()
                        ->label('')
                        ->using(function ($query) {
                            if ($this->filteredUserIds === null) {
                                $this->filteredUserIds = $query->pluck('users.id')->toArray();
                            }
                            $grandTotal = 0;
                            foreach ($this->filteredUserIds as $uid) {
                                foreach ($this->dates as $d) {
                                    $key = $uid . '|' . $d;
                                    if (isset($this->attendIndex[$key])) {
                                        $grandTotal += count($this->attendIndex[$key]);
                                    }
                                }
                            }
                            return $grandTotal;
                        })
                );
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

