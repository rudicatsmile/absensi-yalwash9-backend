<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Models\Leave;
use App\Models\LeaveBalance;
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
use Illuminate\Support\Facades\Gate;
use App\Services\FcmService;


class LeavesTable
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

                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
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
                    ->visible(fn(Leave $record) => $record->status === 'pending' && !in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)),

                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn(Leave $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager', 'kepala_sub_bagian'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Approval Permintaan Cuti')
                    ->modalDescription(fn($record) => $record->employee->name . "\n - " . $record->leaveType->name . "\n- " . $record->start_date->format('d/m/Y') . ' - ' . $record->end_date->format('d/m/Y'))
                    ->action(function (Leave $record) {
                        if (!Gate::allows('approve-high', $record) && !Gate::allows('approve-subsection', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Anda tidak memiliki izin untuk mengajukan cuti ini')
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

                            // $year = $record->start_date->year;
                            $year = now()->year;

                            $leaveBalance = LeaveBalance::where('employee_id', $record->employee_id)
                                ->where('leave_type_id', $record->leave_type_id)
                                ->where('year', $year)
                                ->first();

                            // Check if leave balance exists
                            if (!$leaveBalance) {
                                DB::rollBack();

                                \Filament\Notifications\Notification::make()
                                    ->title('Tidak dapat mengajukan cuti ini')
                                    ->danger()
                                    ->body('Saldo cuti tidak ditemukan untuk karyawan ini dan jenis cuti ini.')
                                    ->send();

                                return;
                            }

                            // Check if remaining days is sufficient
                            if ($leaveBalance->remaining_days < $totalDays) {
                                DB::rollBack();

                                \Filament\Notifications\Notification::make()
                                    ->title('Tidak dapat mengajukan cuti ini')
                                    ->danger()
                                    ->body("Saldo cuti tidak mencukupi. Diperlukan: {$totalDays} hari, Tersedia: {$leaveBalance->remaining_days} hari.")
                                    ->send();

                                return;
                            }

                            $record->update([
                                'status' => 'approved',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'total_days' => $totalDays,
                            ]);

                            $leaveBalance->update([
                                'used_days' => $leaveBalance->used_days + $totalDays,
                                'remaining_days' => $leaveBalance->remaining_days - $totalDays,
                                'last_updated' => now(),
                            ]);

                            DB::commit();

                            // Send notification to employee
                            $employee = $record->employee;
                            if ($employee && $employee->id) {
                                $title = 'Pengajuan Cuti Disetujui';
                                $body = 'Pengajuan cuti Anda pada tanggal ' . $record->start_date->format('d/m/Y') . ' telah disetujui.';
                                $data = [
                                    'type' => 'leave_status_update',
                                    'leave_id' => (string) $record->id,
                                ];
                                app(FcmService::class)->sendToUser($employee->id, $title, $body, $data);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Leave request approved successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            \Filament\Notifications\Notification::make()
                                ->title('Failed to approve leave request')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn(Leave $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager', 'kepala_sub_bagian'], true))
                    ->form([
                        Textarea::make('notes')
                            ->label('Rejection Notes')
                            ->rows(3)
                            ->required(),
                    ])
                    ->modalHeading('Reject Leave Request')
                    ->modalDescription(fn($record) => 'Employee: ' . $record->employee->name . "\nLeave Type: " . $record->leaveType->name)
                    ->action(function (Leave $record, array $data) {
                        if (!Gate::allows('approve-high', $record) && !Gate::allows('approve-subsection', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('You are not authorized to reject this leave')
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
                            $title = 'Pengajuan Cuti Ditolak';
                            $body = 'Maaf, pengajuan cuti Anda pada tanggal ' . $record->start_date->format('d/m/Y') . ' ditolak.';
                            $data = [
                                'type' => 'leave_status_update',
                                'leave_id' => (string) $record->id,
                            ];
                            app(FcmService::class)->sendToUser($employee->id, $title, $body, $data);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Leave request rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => in_array(auth()->user()->role, ['admin', 'manager', 'kepala_lembaga'], true)),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
