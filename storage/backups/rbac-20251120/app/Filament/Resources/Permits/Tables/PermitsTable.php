<?php

namespace App\Filament\Resources\Permits\Tables;

use App\Models\Permit;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PermitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('permitType.name')
                    ->label('Permit Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->sortable(),

                IconColumn::make('attachment_url')
                    ->label('Attachment')
                    ->icon(fn ($record) => $record->attachment_url ? 'heroicon-o-paper-clip' : null)
                    ->color('primary')
                    ->url(fn ($record) => $record->attachment_url ? Storage::url($record->attachment_url) : null)
                    ->openUrlInNewTab()
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->attachment_url ? 'View Attachment' : 'No Attachment'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable(),

                SelectFilter::make('permit_type_id')
                    ->label('Permit Type')
                    ->relationship('permitType', 'name')
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From'),
                        DatePicker::make('end_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),

                EditAction::make()
                    ->label('Edit')
                    ->visible(fn (Permit $record) => $record->status === 'pending'),

                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Permit $record) => $record->status === 'pending' && (auth()->user()->role === 'admin' || auth()->user()->role === 'hr'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Permit Request')
                    ->modalDescription(fn ($record) => 'Employee: '.$record->employee->name."\nPermit Type: ".$record->permitType->name."\nDates: ".$record->start_date->format('d/m/Y').' - '.$record->end_date->format('d/m/Y'))
                    ->action(function (Permit $record) {
                        try {
                            DB::beginTransaction();

                            $totalDays = WorkdayCalculator::countWorkdaysExcludingHolidays(
                                Carbon::parse($record->start_date),
                                Carbon::parse($record->end_date)
                            );

                            $record->update([
                                'status' => 'approved',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'total_days' => $totalDays,
                            ]);

                            DB::commit();

                            \Filament\Notifications\Notification::make()
                                ->title('Permit request approved successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            \Filament\Notifications\Notification::make()
                                ->title('Failed to approve permit request')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Permit $record) => $record->status === 'pending' && (auth()->user()->role === 'admin' || auth()->user()->role === 'hr'))
                    ->form([
                        Textarea::make('notes')
                            ->label('Rejection Notes')
                            ->rows(3)
                            ->required(),
                    ])
                    ->modalHeading('Reject Permit Request')
                    ->modalDescription(fn ($record) => 'Employee: '.$record->employee->name."\nPermit Type: ".$record->permitType->name)
                    ->action(function (Permit $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'notes' => $data['notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Permit request rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}