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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
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
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('departemen.name')
                    ->label('Unit Kerja')
                    ->sortable()
                    ->placeholder('Belum diset'),
                BadgeColumn::make('attendance_status')
                    ->label('Status Absen')
                    ->state(function (User $record): ?string {
                        $att = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
                            ->first();
                        if (!$att)
                            return null;
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
                    ->color(function (User $record): ?string {
                        $att = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
                            ->first();
                        if (!$att)
                            return null;
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
                    ->visible(function (User $record) {
                        return !\App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
                            ->exists();
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
                    ->action(function (User $record, array $data) {
                        $today = now()->toDateString();
                        $latlon = trim((string) ($data['latlon_in'] ?? '-6.1914783,106.9372911'));
                        if ($latlon === '') {
                            Notification::make()
                                ->title('Lokasi tidak tersedia')
                                ->body('Gagal mendapatkan lokasi. Pastikan GPS aktif, izin lokasi diberikan, dan perangkat mendukung Geolocation.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $attendance = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', $today)
                            ->first();

                        if (!$attendance) {
                            $attendance = new \App\Models\Attendance();
                            $attendance->user_id = $record->id;
                            $attendance->date = $today;
                        }

                        $attendance->status = $data['status'] ?? 'on_time';
                        $attendance->company_location_id = $data['company_location_id'] ?? null;
                        $attendance->shift_id = $data['shift_id'] ?? null;
                        if (($data['status'] ?? null) === 'permit') {
                            $attendance->file = $data['file'] ?? null;
                        } else {
                            $attendance->file = null;
                        }
                        $attendance->time_in = now()->format('H:i:s');
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
                            ->body('Status absensi berhasil disimpan.')
                            ->success()
                            ->send();
                    }),
                Action::make('update')
                    ->label('Update')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (User $record) {
                        return \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
                            ->exists();
                    })
                    ->form(function (User $record) {
                        $att = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
                            ->first();
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
                    ->action(function (User $record, array $data) {
                        $att = \App\Models\Attendance::query()
                            ->where('user_id', $record->id)
                            ->whereDate('date', now()->toDateString())
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
