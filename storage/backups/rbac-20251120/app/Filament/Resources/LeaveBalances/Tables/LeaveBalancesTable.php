<?php

namespace App\Filament\Resources\LeaveBalances\Tables;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                TextColumn::make('quota_days')
                    ->label('Quota Days')
                    ->sortable(),

                TextColumn::make('used_days')
                    ->label('Used Days')
                    ->sortable(),

                TextColumn::make('remaining_days')
                    ->label('Remaining Days')
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),

                TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable(),

                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->searchable(),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $currentYear = now()->year;

                        return collect(range($currentYear - 2, $currentYear + 1))
                            ->mapWithKeys(fn ($year) => [$year => $year]);
                    }),
            ])
            ->recordActions([
            ])
            ->headerActions([
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'manager'),
                ]),
            ])
            ->defaultSort('employee.name', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}