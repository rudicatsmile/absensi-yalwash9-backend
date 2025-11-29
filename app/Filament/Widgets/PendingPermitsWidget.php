<?php

namespace App\Filament\Widgets;

use App\Models\Permit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingPermitsWidget extends BaseWidget
{
    protected static ?string $heading = 'Izin Menunggu Persetujuan';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        $currentUser = auth()->user();
        $query = Permit::with(['employee:id,name,departemen_id', 'permitType:id,name'])
            ->where('status', 'pending');

        // Filter berdasarkan role
        if (in_array($currentUser->role, ['employee', 'kepala_sub_bagian'])) {
            // Employee dan kepala_sub_bagian hanya melihat izin mereka sendiri
            $query->where('employee_id', $currentUser->id);
        } elseif ($currentUser->role === 'manager') {
            // Manager melihat izin dari departemen mereka
            $query->whereHas('employee', function ($q) use ($currentUser) {
                $q->where('departemen_id', $currentUser->departemen_id);
            });
        }
        // Admin dan kepala_lembaga melihat semua izin pending (tidak ada filter tambahan)

        return $table
            ->query(
                $query->latest('created_at')->limit(10)
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable(),

                TextColumn::make('permitType.name')
                    ->label('Jenis Izin')
                    ->badge()
                    ->color('info'),

                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y'),

                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y'),

                TextColumn::make('total_days')
                    ->label('Durasi')
                    ->suffix(' hari')
                    ->alignCenter(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        default => 'Menunggu',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'warning',
                    }),
            ])
            ->paginated(false);
    }
}
