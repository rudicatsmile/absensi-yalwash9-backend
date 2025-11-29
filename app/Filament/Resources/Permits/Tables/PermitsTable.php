<?php

namespace App\Filament\Resources\Permits\Tables;

use App\Models\Permit;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use App\Services\FcmService;
use App\Models\UserPushToken;
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
use Illuminate\Support\Facades\Gate;

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
                    ->icon(fn($record) => $record->attachment_url ? 'heroicon-o-paper-clip' : null)
                    ->color('primary')
                    ->url(fn($record) => $record->attachment_url ? Storage::url($record->attachment_url) : null)
                    ->openUrlInNewTab()
                    ->alignCenter()
                    ->tooltip(fn($record) => $record->attachment_url ? 'View Attachment' : 'No Attachment'),

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
                    ->options(\App\Models\User::query()->orderBy('name')->pluck('name', 'id')->toArray())
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
                                fn(Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),

                EditAction::make()
                    ->label('Edit')
                    ->visible(fn(Permit $record) => $record->status === 'pending' && !in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)),

                Action::make('approve')
                    ->label('Approve ')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn(Permit $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Approval Permintaan Izin')
                    ->modalDescription(fn($record) => 'A/n: ' . $record->employee->name . "\n - " . $record->permitType->name . "\n - " . $record->start_date->format('d/m/Y') . ' - ' . $record->end_date->format('d/m/Y'))
                    ->action(function (Permit $record) {
                        if (!Gate::allows('approve-high', $record) && !Gate::allows('approve-subsection', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada hak akses untuk mengapprove permintaan ini')
                                ->danger()
                                ->send();
                            return;
                        }
                        try {
                            DB::beginTransaction();

                            // Recalculate total days to ensure consistency with holidays
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

                            //TODO: Send notification to employee
                            // Send notification to employee
                            $employee = $record->employee;
                            if ($employee && $employee->id) {
                                $title = 'Pengajuan Izin Disetujui';
                                $body = 'Pengajuan izin Anda pada tanggal ' . $record->start_date->format('d/m/Y') . ' telah disetujui.';
                                $data = [
                                    'type' => 'permit_status_update',
                                    'permit_id' => (string) $record->id,
                                    'event_id' => (string) $record->shift_id,
                                ];
                                app(FcmService::class)->sendToUser($employee->id, $title, $body, $data);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Permintaan izin disetujui')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            \Filament\Notifications\Notification::make()
                                ->title('Gagal menyetujui permintaan izin')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn(Permit $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga'], true))
                    ->form([
                        Textarea::make('notes')
                            ->label('Rejection Notes')
                            ->rows(3)
                            ->required(),
                    ])
                    ->modalHeading('Reject Permit Request')
                    ->modalDescription(fn($record) => 'Employee: ' . $record->employee->name . "\nPermit Type: " . $record->permitType->name)
                    ->action(function (Permit $record, array $data) {
                        if (!Gate::allows('approve-high', $record) && !Gate::allows('approve-subsection', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('You are not authorized to reject this permit')
                                ->danger()
                                ->send();
                            return;
                        }
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'notes' => $data['notes'],
                        ]);

                        // Send notification to employee
                        $employee = $record->employee;
                        if ($employee && $employee->id) {
                            $title = 'Pengajuan Izin Ditolak';
                            $body = 'Maaf, pengajuan izin Anda pada tanggal ' . $record->start_date->format('d/m/Y') . ' ditolak.';
                            $data = [
                                'type' => 'permit_status_update',
                                'permit_id' => (string) $record->id,
                                'event_id' => (string) $record->shift_id,
                            ];
                            app(FcmService::class)->sendToUser($employee->id, $title, $body, $data);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Permit request rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->check() && in_array(auth()->user()->role, ['admin', 'kepala_lembaga'], true)),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
