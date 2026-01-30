<?php

namespace App\Filament\Resources\JamKerjas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Carbon\Carbon;

class JamKerjasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Shift Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->iconPosition(IconPosition::Before),

                TextColumn::make('end_time')
                    ->label('End Time')
                    ->time('H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->iconPosition(IconPosition::Before),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        try {
                            $start = Carbon::parse($record->start_time);
                            $end = Carbon::parse($record->end_time);

                            if ($record->is_cross_day) {
                                $end->addDay();
                            }

                            $hours = $start->diffInHours($end);
                            $minutes = $start->diffInMinutes($end) % 60;

                            $duration = '';
                            if ($hours > 0)
                                $duration .= "{$hours} hr ";
                            if ($minutes > 0)
                                $duration .= "{$minutes} min";

                            return trim($duration) ?: '0 min';
                        } catch (\Exception $e) {
                            return '-';
                        }
                    })
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_cross_day')
                    ->label('Cross Midnight')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('grace_period_minutes')
                    ->label('Grace Period')
                    ->suffix(' min')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->alignCenter(),

                // Removed users_count as JamKerja doesn't have users relationship yet

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Shifts')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),

                TernaryFilter::make('is_cross_day')
                    ->label('Cross Midnight')
                    ->placeholder('All Shifts')
                    ->trueLabel('Cross Midnight')
                    ->falseLabel('Same Day'),
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
            ->defaultSort('name', 'asc');
    }
}
