<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use UnitEnum;

class LaporanAbsensi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Laporan Absensi';

    protected string $view = 'filament.pages.laporan-absensi';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.departemen.name')
                    ->label('Departemen')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Jam Keluar')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('working_hours')
                    ->label('Jam Kerja')
                    ->state(function (Attendance $record): string {
                        if (!$record->time_in || !$record->time_out) {
                            return '-';
                        }

                        $timeIn = Carbon::parse($record->time_in);
                        $timeOut = Carbon::parse($record->time_out);
                        $diff = $timeIn->diff($timeOut);

                        return sprintf('%d jam %d menit', $diff->h, $diff->i);
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (Attendance $record): string {
                        if (!$record->time_in) {
                            return 'Tidak Masuk';
                        }

                        if (!$record->time_out) {
                            return 'Belum Pulang';
                        }

                        return 'Hadir';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Belum Pulang' => 'warning',
                        'Tidak Masuk' => 'danger',
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now()->subDays(30)),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $data['start_date']),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $data['end_date']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators['start_date'] = 'Dari: ' . Carbon::parse($data['start_date'])->format('d/m/Y');
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators['end_date'] = 'Sampai: ' . Carbon::parse($data['end_date'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn() => !auth()->check() || auth()->user()->role !== 'employee'),

                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn(Builder $query): Builder => $query->whereDate('date', now()))
                    ->toggle(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('date', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]))
                    ->toggle(),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('date', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]))
                    ->toggle(),
            ])
            ->actions([
                ActionsAction::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(Attendance $record): string => "Detail Absensi - {$record->user->name}")
                    ->modalContent(function (Attendance $record): string {
                        $timeIn = $record->time_in ? Carbon::parse($record->time_in)->format('H:i') : '-';
                        $timeOut = $record->time_out ? Carbon::parse($record->time_out)->format('H:i') : '-';
                        $workingHours = '-';

                        if ($record->time_in && $record->time_out) {
                            $diff = Carbon::parse($record->time_in)->diff(Carbon::parse($record->time_out));
                            $workingHours = sprintf('%d jam %d menit', $diff->h, $diff->i);
                        }

                        return view('filament.modals.attendance-detail', [
                            'record' => $record,
                            'timeIn' => $timeIn,
                            'timeOut' => $timeOut,
                            'workingHours' => $workingHours,
                        ])->render();
                    }),
            ])
            ->headerActions([
                ActionsAction::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn() => $this->exportPdf()),

                ActionsAction::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->action('exportExcel'),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        $query = Attendance::query()
            ->with(['user.departemen'])
            ->select('id', 'user_id', 'date', 'time_in', 'time_out', 'latlon_in', 'latlon_out', 'created_at', 'updated_at')
            ->orderBy('date', 'desc');

        if (auth()->check() && auth()->user()->role === 'employee') {
            $query->where('user_id', auth()->id());
        }

        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
            $query->whereHas('user', fn(Builder $uq) => $uq->where('departemen_id', auth()->user()->departemen_id));
        }

        return $query;
    }

    public function exportPdf()
    {
        Log::info('Export PDF triggered.');
        try {
            // Get filtered data from the table
            $query = $this->getFilteredTableQuery();
            $attendances = $query->with(['user.departemen'])->get();

            Log::info('Fetched ' . $attendances->count() . ' records.');

            // Create PDF using blade view
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.laporan-absensi-pdf', [
                'attendances' => $attendances,
                'exported_at' => now()->format('d/m/Y H:i'),
                'total_records' => $attendances->count(),
            ])->setPaper('A4', 'landscape');

            $filename = 'laporan-absensi-' . now()->format('d-m-Y-H-i-s') . '.pdf';

            Log::info('PDF generated, returning download response.');

            return response()->streamDownload(function () use ($pdf) {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                echo $pdf->output();
            }, $filename);
        } catch (\Throwable $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            Notification::make()
                ->title('Export PDF Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function exportExcel()
    {
        try {
            // Get filtered data from the table
            $query = $this->getFilteredTableQuery();
            $attendances = $query->with(['user.departemen'])->get();

            $csvData = "No,Nama Karyawan,Departemen,Tanggal,Jam Masuk,Jam Keluar,Jam Kerja,Status\n";

            foreach ($attendances as $index => $attendance) {
                $timeIn = $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '-';
                $timeOut = $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '-';

                $workingHours = '-';
                if ($attendance->time_in && $attendance->time_out) {
                    $diff = Carbon::parse($attendance->time_in)->diff(Carbon::parse($attendance->time_out));
                    $workingHours = sprintf('%d jam %d menit', $diff->h, $diff->i);
                }

                $status = 'Hadir';
                if (!$attendance->time_in) {
                    $status = 'Tidak Masuk';
                } elseif (!$attendance->time_out) {
                    $status = 'Belum Pulang';
                }

                $csvData .= sprintf(
                    "%d,%s,%s,%s,%s,%s,%s,%s\n",
                    $index + 1,
                    $attendance->user->name ?? 'User Terhapus',
                    $attendance->user?->departemen?->name ?? $attendance->user?->department ?? '-',
                    Carbon::parse($attendance->date)->format('d/m/Y'),
                    $timeIn,
                    $timeOut,
                    $workingHours,
                    $status
                );
            }

            $filename = 'laporan-absensi-' . now()->format('d-m-Y-H-i-s') . '.csv';

            Notification::make()
                ->title('Excel berhasil diunduh')
                ->success()
                ->send();

            return Response::make($csvData, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Excel Gagal')
                ->body('Terjadi kesalahan saat mengexport Excel: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
