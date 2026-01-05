<?php

namespace App\Filament\Resources\ManualAttendances;

use App\Filament\Resources\ManualAttendances\Pages\ListManualAttendances;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ManualAttendanceResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Absensi Manual';
    protected static ?string $pluralLabel = 'Absensi Manual';
    protected static ?int $navigationSort = 12;

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->select(['id', 'name', 'email', 'departemen_id'])
                    ->orderBy('nip', 'asc')
            )
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->filters([
                Filter::make('date_filter')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->closeOnDateSelection()
                            ->live()
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $date = $data['date'] ?? now()->toDateString();
                        return $query; // Filter query sebenarnya tidak diperlukan karena kita memfilter relasi di kolom status, tapi kita simpan filternya untuk state.
                    })
                    ->indicateUsing(function (array $data): array {
                        $date = $data['date'] ?? null;
                        if (!$date)
                            return [];
                        return ['Tanggal: ' . \Carbon\Carbon::parse($date)->format('d-m-Y')];
                    }),
                SelectFilter::make('departemen_id')
                    ->label('Departemen')
                    ->options(\App\Models\Departemen::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (!is_numeric($value)) {
                            return $query;
                        }
                        return $query->where('departemen_id', (int) $value);
                    })
                    ->indicateUsing(function (array $data): array {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return [];
                        }
                        $name = \App\Models\Departemen::query()->whereKey($value)->value('name');
                        return $name ? ['Departemen: ' . $name] : [];
                    }),
                SelectFilter::make('shift_kerja_id')
                    ->label('Shift Kerja')
                    ->options(\App\Models\ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->default(fn() => \App\Models\ShiftKerja::where('name', 'Satu Shift')->value('id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query; // Filter query sebenarnya tidak diperlukan karena kita memfilter relasi di kolom status
                    })
                    ->indicateUsing(function (array $data): array {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return [];
                        }
                        $name = \App\Models\ShiftKerja::query()->whereKey($value)->value('name');
                        return $name ? ['Shift: ' . $name] : [];
                    }),
                Filter::make('presence_status')
                    ->label('Kehadiran')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status Kehadiran')
                            ->options([
                                'attended' => 'Sudah Absen',
                                'not_attended' => 'Belum Absen',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['status'] ?? null;
                        if (!$status)
                            return $query;

                        // Ambil tanggal dari filter 'date_filter', jika tidak ada gunakan hari ini
                        $tableFilters = request()->input('tableFilters', []);
                        $date = $tableFilters['date_filter']['date'] ?? now()->toDateString();

                        if ($status === 'attended') {
                            return $query->whereHas('attendances', function ($q) use ($date) {
                                $q->whereDate('date', $date);
                            });
                        } elseif ($status === 'not_attended') {
                            return $query->whereDoesntHave('attendances', function ($q) use ($date) {
                                $q->whereDate('date', $date);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (($data['status'] ?? null) === 'attended')
                            return 'Sudah Absen';
                        if (($data['status'] ?? null) === 'not_attended')
                            return 'Belum Absen';
                        return null;
                    }),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('departemen.name')
                    ->label('Unit Kerja')
                    ->sortable()
                    ->placeholder('Belum diset'),
                TextColumn::make('attendance_location')
                    ->label('Lokasi Absen')
                    ->state(function (User $record, \Livewire\Component $livewire): ?string {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = null;
                        try {
                            $date = \Carbon\Carbon::parse($dateInput)->toDateString();
                        } catch (\Throwable $e) {
                            $date = now()->toDateString();
                        }

                        $query = \App\Models\Attendance::query()
                            ->with('companyLocation')
                            ->where('user_id', $record->id)
                            ->whereDate('date', $date);

                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }

                        $att = $query->first();

                        return $att?->companyLocation?->name ?? '-';
                    }),
                TextColumn::make('shift_kerja')
                    ->label('Shift Kerja')
                    ->state(function (User $record, \Livewire\Component $livewire): ?string {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = null;
                        try {
                            $date = \Carbon\Carbon::parse($dateInput)->toDateString();
                        } catch (\Throwable $e) {
                            $date = now()->toDateString();
                        }

                        $query = \App\Models\Attendance::query()
                            ->with('shift')
                            ->where('user_id', $record->id)
                            ->whereDate('date', $date);

                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }

                        $att = $query->first();

                        return $att?->shift?->name ?? '-';
                    }),
                BadgeColumn::make('attendance_status')
                    ->label('Status Absen')
                    ->state(function (User $record, \Livewire\Component $livewire): ?string {
                        // Ambil tanggal dari filter, default ke hari ini
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = null;
                        try {
                            $date = \Carbon\Carbon::parse($dateInput)->toDateString();
                        } catch (\Throwable $e) {
                            $date = now()->toDateString();
                        }

                        try {
                            $query = \App\Models\Attendance::query()
                                ->where('user_id', $record->id)
                                ->whereDate('date', $date);

                            if ($shiftId) {
                                $query->where('shift_id', $shiftId);
                            }

                            $att = $query->first();
                        } catch (\Throwable $e) {
                            return 'Gagal Memuat';
                        }
                        if (!$att)
                            return '-';
                        $time = $att->time_in ? substr((string) $att->time_in, 0, 5) : '-';
                        $map = [
                            'on_time' => 'Hadir',
                            'late' => 'Terlambat',
                            'absent' => 'Tidak Hadir',
                            'permit' => 'Izin',
                        ];
                        $label = $map[$att->status] ?? $att->status;
                        return $label . ' (' . $time . ')';
                    })
                    ->color(function (User $record, \Livewire\Component $livewire): ?string {
                        // Ambil tanggal dari filter, default ke hari ini
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = null;
                        try {
                            $date = \Carbon\Carbon::parse($dateInput)->toDateString();
                        } catch (\Throwable $e) {
                            $date = now()->toDateString();
                        }

                        try {
                            $query = \App\Models\Attendance::query()
                                ->where('user_id', $record->id)
                                ->whereDate('date', $date);

                            if ($shiftId) {
                                $query->where('shift_id', $shiftId);
                            }

                            $att = $query->first();
                        } catch (\Throwable $e) {
                            return 'danger';
                        }
                        if (!$att)
                            return 'warning'; // Warna kuning/oranye untuk "Belum Absen"
                        return match ($att->status) {
                            'on_time' => 'success',
                            'late' => 'danger',
                            'absent' => 'danger',
                            'permit' => 'info',
                            default => 'gray',
                        };
                    }),
            ])
            ->recordActions([
                Action::make('kehadiran')
                    ->label('Kehadiran')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->modalHeading('Ubah Status Kehadiran')
                    ->visible(function (User $record, \Livewire\Component $livewire) {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = \Carbon\Carbon::parse($dateInput)->toDateString();

                        $query = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $date);

                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }

                        return !$query->exists();
                    })
                    ->form([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options([
                                'on_time' => 'Hadir',
                                'absent' => 'Tidak hadir',
                                'permit' => 'Izin',
                            ])
                            ->inline()
                            ->required()
                            ->default('on_time')
                            ->reactive(),
                        Select::make('company_location_id')
                            ->label('Lokasi Absen')
                            ->options(\App\Models\CompanyLocation::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(session('manual_attendance_location_id')),
                        Select::make('shift_id')
                            ->label('Shift Kerja')
                            ->options(\App\Models\ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(session('manual_attendance_shift_id')),
                        TextInput::make('latlon_in')
                            ->hidden()
                            ->extraInputAttributes([
                                'x-init' => <<<'JS'
                                    if (!navigator.geolocation) {
                                        console.warn('Geolocation is not supported by this browser.');
                                        return;
                                    }

                                    // Cek consent dari localStorage (jika ada overlay consent)
                                    const consent = localStorage.getItem('locationConsent');
                                    if (consent === 'deny') {
                                        console.warn('User denied location consent via overlay.');
                                        return;
                                    }

                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            const lat = position.coords.latitude;
                                            const lon = position.coords.longitude;
                                            $el.value = `${lat},${lon}`;
                                            $el.dispatchEvent(new Event('input', { bubbles: true }));
                                        },
                                        (error) => {
                                            console.error('Geolocation error:', error.message);
                                            // Nilai tetap kosong, akan ditangani oleh validasi server
                                        },
                                        {
                                            enableHighAccuracy: true,
                                            timeout: 10000,
                                            maximumAge: 0
                                        }
                                    );
                                JS,
                            ]),
                        FileUpload::make('file')
                            ->label('Berkas (wajib untuk Izin)')
                            ->directory('attendance-files')
                            ->visibility('private')
                            ->downloadable()
                            ->openable()
                            ->maxSize(5_000)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->visible(fn($get) => $get('status') === 'permit')
                            ->required(fn($get) => $get('status') === 'permit'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data, \Livewire\Component $livewire) {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $targetDate = \Carbon\Carbon::parse($dateInput)->toDateString();

                        // Validasi Departemen User (Requirement: Handle kasus tidak ditemukan)
                        if (!$record->departemen_id) {
                            Notification::make()
                                ->title('Gagal Proses Absensi')
                                ->body('Pegawai ini belum terdaftar dalam departemen manapun. Silakan atur departemen pegawai terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $latlon = trim((string) ($data['latlon_in'] ?? '-6.1914783,106.9372911'));
                        if ($latlon === '') {
                            Notification::make()
                                ->title('Lokasi tidak tersedia')
                                ->body('Gagal mendapatkan lokasi. Pastikan GPS aktif, izin lokasi diberikan, dan perangkat mendukung Geolocation.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $filters = $livewire->tableFilters ?? [];
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $query = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $targetDate);
                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }
                        $attendance = $query->first();

                        if (!$attendance) {
                            $attendance = new \App\Models\Attendance();
                            $attendance->user_id = $record->id;
                            $attendance->date = $targetDate;
                            // Requirement: Tambahkan field 'departemen_id' ke dalam data yang akan diinsert
                            $attendance->departemen_id = $record->departemen_id;
                        }

                        $attendance->status = $data['status'] ?? 'on_time';
                        $attendance->company_location_id = $data['company_location_id'] ?? null;
                        $attendance->shift_id = $data['shift_id'] ?? null;
                        if (($data['status'] ?? null) === 'permit') {
                            $attendance->file = $data['file'] ?? null;
                        } else {
                            $attendance->file = null;
                        }
                        // Jika membuat baru, set time_in. Jika update (harusnya lewat update action, tapi jaga-jaga), jangan ubah time_in kecuali diperlukan.
                        // Action ini untuk 'kehadiran' (create new), jadi set time_in.
                        if (!$attendance->exists) {
                            $attendance->time_in = now()->format('H:i:s');
                        }
                        $attendance->latlon_in = $latlon;
                        $attendance->save();

                        session([
                            'manual_attendance_location_id' => $attendance->company_location_id,
                            'manual_attendance_shift_id' => $attendance->shift_id,
                        ]);

                        Log::info('audit:manual_attendance.update', [
                            'actor_id' => auth()->id(),
                            'user_id' => $record->id,
                            'status' => $attendance->status,
                            'date' => $attendance->date,
                        ]);

                        Notification::make()
                            ->title('Kehadiran diperbarui')
                            ->body('Status absensi berhasil disimpan untuk tanggal ' . \Carbon\Carbon::parse($targetDate)->format('d-m-Y'))
                            ->success()
                            ->send();
                    }),
                Action::make('update')
                    ->label('Update')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (User $record, \Livewire\Component $livewire) {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $date = \Carbon\Carbon::parse($dateInput)->toDateString();

                        $query = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $date);
                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }
                        return $query->exists();
                    })
                    ->form(function (User $record, \Livewire\Component $livewire) {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $date = \Carbon\Carbon::parse($dateInput)->toDateString();

                        $shiftId = $filters['shift_kerja_id']['value'] ?? null;
                        $query = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $date);
                        if ($shiftId) {
                            $query->where('shift_id', $shiftId);
                        }
                        $att = $query->first();
                        return [
                            Select::make('company_location_id')
                                ->label('Lokasi Absen')
                                ->options(\App\Models\CompanyLocation::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($att->company_location_id ?? null),
                            Select::make('shift_id')
                                ->label('Shift Kerja')
                                ->options(\App\Models\ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($att->shift_id ?? null),
                            ToggleButtons::make('status')
                                ->label('Status')
                                ->options([
                                    'on_time' => 'Hadir',
                                    'late' => 'Terlambat',
                                    'absent' => 'Tidak hadir',
                                    'permit' => 'Izin',
                                ])
                                ->inline()
                                ->required()
                                ->default($att->status ?? 'on_time')
                                ->reactive(),
                            TimePicker::make('time_in')
                                ->label('Jam Absen')
                                ->seconds(false)
                                ->required()
                                ->default($att && $att->time_in ? substr((string) $att->time_in, 0, 5) : now()->format('H:i')),
                        ];
                    })
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data, \Livewire\Component $livewire) {
                        $filters = $livewire->tableFilters ?? [];
                        $dateInput = $filters['date_filter']['date'] ?? now()->toDateString();
                        $targetDate = \Carbon\Carbon::parse($dateInput)->toDateString();

                        $att = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $targetDate)
                            ->first();
                        if (!$att) {
                            Notification::make()
                                ->title('Absensi belum dibuat')
                                ->body('Silakan buat absensi terlebih dahulu melalui tombol Kehadiran.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $att->company_location_id = $data['company_location_id'] ?? null;
                        $att->shift_id = $data['shift_id'] ?? null;
                        $att->status = $data['status'] ?? $att->status;
                        $att->time_in = isset($data['time_in']) ? (is_string($data['time_in']) ? $data['time_in'] . ':00' : $data['time_in']) : $att->time_in;
                        $att->save();

                        session([
                            'manual_attendance_location_id' => $att->company_location_id,
                            'manual_attendance_shift_id' => $att->shift_id,
                        ]);

                        Notification::make()
                            ->title('Absensi diperbarui')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(true)
            ->defaultSort('nip', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManualAttendances::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee'], true);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee'], true);
    }
}
