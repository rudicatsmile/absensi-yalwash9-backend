<?php

namespace App\Filament\Resources\Permits\Tables;

use App\Models\Permit;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use App\Services\FcmService;
use App\Models\UserPushToken;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Http\Request;


class PermitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('permitType.name')
                    ->label('Tipe Izin')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Total hari')
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
                    ->label('Persetujuan')
                    ->description(fn(Permit $record) => $record->approved_at ? $record->approved_at->format('d/m/Y H:i') : null)
                    ->sortable()
                    ->searchable()
                    ->placeholder('Belum Disetujui'),

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
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat Detail'),

                    EditAction::make()
                        ->label('Edit')
                        ->visible(fn(Permit $record) => $record->status === 'pending' && !in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)),

                    Action::make('approve')
                        ->label('Setujui')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn(Permit $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager', 'kepala_sub_bagian'], true))
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

                                //Do todo if permit type is 4 (Izin Dinas dianggap masuk kerja)
                                if ($record->permit_type_id == 4) {
                                    // Get default location (Gedung A)
                                    $companyLocation = \App\Models\CompanyLocation::find(1);
                                    $defaultLatLon = $companyLocation ? "{$companyLocation->latitude},{$companyLocation->longitude}" : '-6.1914783,106.9372911';

                                    // Generate attendance for each day in the range
                                    $period = \Carbon\CarbonPeriod::create($record->start_date, $record->end_date);

                                    foreach ($period as $date) {
                                        // Skip non-working days (weekends/holidays) to match total_days logic
                                        if (WorkdayCalculator::isNonWorkingDay($date)) {
                                            continue;
                                        }

                                        // Check if attendance already exists to avoid duplicates
                                        $exists = \App\Models\Attendance::where('user_id', $record->employee_id)
                                            ->whereDate('date', $date)
                                            ->exists();

                                        if ($exists) {
                                            continue;
                                        }

                                        //Create attendance record
                                        $attendanceData = [
                                            'user_id' => $record->employee_id,
                                            'shift_id' => $record->shift_id,
                                            'company_location_id' => 1,    //Default Gedung A
                                            'departemen_id' => $record->employee->departemen_id,
                                            'date' => $date->format('Y-m-d'),
                                            'time_in' => now()->setTimezone('Asia/Jakarta')->format('H:i:s'),
                                            'time_out' => now()->setTimezone('Asia/Jakarta')->format('H:i:s'),
                                            'latlon_in' => $defaultLatLon,
                                            'latlon_out' => $defaultLatLon,
                                            'status' => 'on_time',
                                            'is_work_permit' => true,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ];
                                        DB::enableQueryLog();
                                        \App\Models\Attendance::create($attendanceData);
                                        $queries = DB::getQueryLog();
                                        $lastQuery = end($queries);

                                        // Format raw query with bindings
                                        $rawSql = vsprintf(str_replace('?', "'%s'", $lastQuery['query']), $lastQuery['bindings']);

                                        \Illuminate\Support\Facades\Log::info('Creating attendance from Permit Approval (Type 4) - Raw Query:', [
                                            'sql' => $rawSql,
                                            'bindings' => $lastQuery['bindings']
                                        ]);
                                    }
                                }

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
                        ->label('Tolak')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn(Permit $record) => $record->status === 'pending' && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager', 'kepala_sub_bagian'], true))
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
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Tindakan'),
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

    private function getGeoLocation(Request $request)
    {
        // 1. Coba ambil dari input request (GPS perangkat)
        $latlon = $request->input('latlon_in');

        // 2. Validasi format latitude,longitude
        if ($latlon && preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $latlon)) {
            return $latlon;
        }

        // 3. Fallback: Gunakan lokasi default perusahaan (Gedung A / ID 1)
        // Ini menangani kasus: Perangkat tidak mendukung GPS, Izin lokasi ditolak, atau Sinyal GPS hilang
        try {
            $defaultLocation = \App\Models\CompanyLocation::find(1);
            if ($defaultLocation) {
                return "{$defaultLocation->latitude},{$defaultLocation->longitude}";
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        // 4. Default hardcoded jika database gagal
        return '-6.1914783,106.9372911';
    }
}
