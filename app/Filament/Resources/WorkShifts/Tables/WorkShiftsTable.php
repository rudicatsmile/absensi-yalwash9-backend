<?php

namespace App\Filament\Resources\WorkShifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Work Shift')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('start_time')
                    ->label('Waktu Mulai')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => 
                        \Carbon\Carbon::createFromFormat('H:i:s', $state)->format('H:i')
                    ),

                TextColumn::make('end_time')
                    ->label('Waktu Selesai')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => 
                        \Carbon\Carbon::createFromFormat('H:i:s', $state)->format('H:i')
                    ),

                TextColumn::make('duration_formatted')
                    ->label('Durasi')
                    ->sortable(false)
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_cross_day')
                    ->label('Lintas Hari')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('grace_period_minutes')
                    ->label('Grace Period')
                    ->sortable()
                    ->suffix(' menit')
                    ->color('warning'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('users_count')
                    ->label('Jumlah Karyawan')
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Tidak Aktif',
                    ])
                    ->placeholder('Semua Status'),

                SelectFilter::make('is_cross_day')
                    ->label('Lintas Hari')
                    ->options([
                        1 => 'Ya',
                        0 => 'Tidak',
                    ])
                    ->placeholder('Semua Tipe'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
