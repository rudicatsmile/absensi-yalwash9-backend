<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('time_in')
                    ->label('Check In')
                    ->time('H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success'),
                TextColumn::make('time_out')
                    ->label('Check Out')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'on_time' => 'On Time',
                        'late' => 'Late',
                        'absent' => 'Absent',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'on_time' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(function ($record) {
                        if (!$record->time_out) {
                            return '-';
                        }
                        $checkIn = \Carbon\Carbon::parse($record->time_in);
                        $checkOut = \Carbon\Carbon::parse($record->time_out);
                        $duration = $checkIn->diff($checkOut);

                        return sprintf('%d:%02d hrs', $duration->h, $duration->i);
                    })
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-clock'),
                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('primary')
                    ->placeholder('No Shift')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('latlon_in')
                    ->label('Check In Location')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latlon_out')
                    ->label('Check Out Location')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->default(\Carbon\Carbon::today()),
                        \Filament\Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->default(\Carbon\Carbon::today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $from = $data['date_from'] ?? null;
                        $to   = $data['date_to'] ?? null;

                        if ($from && $to && $from === $to) {
                            $indicators[] = 'Today: ' . \Carbon\Carbon::parse($from)->format('d M Y');
                            return $indicators;
                        }

                        if ($from) {
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($from)->format('d M Y');
                        }
                        if ($to) {
                            $indicators[] = 'To: ' . \Carbon\Carbon::parse($to)->format('d M Y');
                        }

                        return $indicators;
                    }),


                //-----------------
                \Filament\Tables\Filters\Filter::make('departemen_id')
                    ->form([
                        \Filament\Forms\Components\Select::make('departemen_id')
                            ->label('Departemen')
                            ->options(
                                \App\Models\Departemen::query()
                                    // ->orderBy('urut')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('user_id', null);
                            }),

                        \Filament\Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(function (callable $get) {
                                $departemenId = $get('departemen_id');

                                $query = \App\Models\User::query()
                                    ->select('id', 'nip', 'name');

                                if (is_array($departemenId) && count($departemenId)) {
                                    $ids = array_map(static fn($v) => (int) $v, $departemenId);
                                    $query->whereIn('departemen_id', $ids);
                                } elseif (is_numeric($departemenId)) {
                                    $query->where('departemen_id', (int) $departemenId);
                                }

                                return $query->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['departemen_id'] ?? null, function ($query, $departemenId) {
                                if (is_array($departemenId)) {
                                    return $query->whereIn('departemen_id', array_map('intval', $departemenId));
                                }
                                return $query->where('departemen_id', (int) $departemenId);
                            })
                            ->when($data['user_id'] ?? null, function ($query, $userId) {
                                return $query->where('user_id', (int) $userId);
                            });
                    }),




                //-----------------

                // Tambahan: Dropdown Departemen

                SelectFilter::make('status')
                    ->options([
                        'on_time' => 'On Time',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ])
                    ->multiple(),
                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredSortedTableQuery();

                        if (!$query) {
                            return null;
                        }

                        $attendances = (clone $query)
                            ->reorder()
                            ->orderByDesc('date')
                            ->orderByDesc('time_in')
                            ->with(['user', 'shift'])
                            ->get();

                        $csv = "User,Date,Check In,Check Out,Status,Total Hours,Shift\n";
                        foreach ($attendances as $attendance) {
                            $totalHours = '-';
                            if ($attendance->time_out) {
                                $checkIn = \Carbon\Carbon::parse($attendance->time_in);
                                $checkOut = \Carbon\Carbon::parse($attendance->time_out);
                                $duration = $checkIn->diff($checkOut);
                                $totalHours = sprintf('%d:%02d', $duration->h, $duration->i);
                            }

                            $csv .= sprintf(
                                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                                $attendance->user->name,
                                $attendance->date ? \Carbon\Carbon::parse($attendance->date)->format('d M Y') : '-',
                                $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '-',
                                $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '-',
                                ucfirst(str_replace('_', ' ', $attendance->status)),
                                $totalHours,
                                $attendance->shift->name ?? 'No Shift'
                            );
                        }

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'attendances-' . now()->format('Y-m-d') . '.csv');
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    // Helper bersama untuk membaca pilihan Departemen dari state filter (Filament v2/v3), dengan sanitasi.
    private static function getSelectedDepartemenIds(): array
    {
        // ... existing code ...
        try {
            // Dukung 'tableFilters' (v3) atau 'filters' (v2)
            $filters = request()->input('tableFilters', request()->input('filters', []));
            $depFilter = $filters['departemen_id'] ?? [];

            // Dukung bentuk 'value' (single) atau 'values' (multiple)
            $raw = $depFilter['values'] ?? ($depFilter['value'] ?? []);

            return collect((array) $raw)
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int) $v)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal membaca state filter Departemen', [
                'exception' => $e,
            ]);
            return [];
        }
        // ... existing code ...
    }
}


