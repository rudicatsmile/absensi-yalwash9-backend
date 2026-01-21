<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\EmployeeWorkSchedule;
use App\Models\ShiftKerja;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                    TextColumn::make('row_number')
                        ->label('No')
                        ->getStateUsing(static function (\Filament\Tables\Columns\TextColumn $column, $record): string {
                            $table = $column->getTable();
                            $livewire = $table->getLivewire();
                            $page = $livewire->getTablePage();
                            $perPage = $livewire->getTableRecordsPerPage();
                            $records = $table->getRecords();
                            $index = $records->values()->search(fn($item) => $item->getKey() === $record->getKey());

                            return (string) (($page - 1) * $perPage + $index + 1);
                        })
                        ->sortable(false),
                    ImageColumn::make('image_url')
                        ->label('Avatar')
                        ->disk('public')
                        ->circular()
                        ->defaultImageUrl(fn() => 'data:image/svg+xml;base64,' . base64_encode('
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" style="background-color: #F3F4F6;">
                            <g transform="translate(25, 25)">
                                <path fill="#9CA3AF" fill-rule="evenodd" d="M-5 -10a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM-8.249 4.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 010 6.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                            </g>
                        </svg>
                    '))
                        ->size(50),
                    TextColumn::make('name')
                        ->label('Nama / Email')
                        ->getStateUsing(function ($record) {
                            $name = e($record->name ?? '');
                            $email = e($record->email ?? '');

                            return "<div>{$name}<div class=\"text-xs italic text-slate-500\">{$email}</div></div>";
                        })
                        ->html()
                        ->searchable(['name', 'email'])
                        ->sortable(),

                    // TextColumn::make('role')
                    //     ->badge()
                    //     ->color(fn (string $state): string => match ($state) {
                    //         'admin' => 'danger',
                    //         'manager' => 'warning',
                    //         'employee' => 'success',
                    //         default => 'gray',
                    //     })
                    //     ->searchable(),
                    // TextColumn::make('jabatan.name')
                    //     ->label('Jabatan')
                    //     ->badge()
                    //     ->color('info')
                    //     ->searchable()
                    //     ->sortable()
                    //     ->placeholder('Belum diset')
                    //     ->icon('heroicon-o-briefcase'),
                    TextColumn::make('departemen.name')
                        ->label('Unit Kerja')
                        ->badge()
                        ->color('primary')
                        ->searchable()
                        ->sortable()
                        ->placeholder('Belum diset')
                        ->icon('heroicon-o-building-library'),
                    // TextColumn::make('shiftKerja.name')
                    //     ->label('Shift')
                    //     ->badge()
                    //     ->color('warning')
                    //     ->searchable()
                    //     ->sortable()
                    //     ->placeholder('Belum diset')
                    //     ->icon('heroicon-o-clock'),

                    TextColumn::make('shift')
                        ->label('Shift Kerja')
                        ->wrap()
                        ->color('warning')
                        ->getStateUsing(function ($record) {
                            // Ambil nama shift kerja dari relasi pivot shiftKerjas (join: shift_kerja_user -> shift_kerjas)
                            $names = $record->shiftKerjas?->pluck('name')->filter()->all() ?? [];

                            return count($names) ? implode(', ', $names) : null;
                        })
                        ->placeholder('Belum diset')
                        ->icon('heroicon-o-clock'),

                    TextColumn::make('location')
                        ->label('Lokasi')
                        ->wrap()
                        ->getStateUsing(function ($record) {
                            // Ambil nama lokasi dari relasi pivot companyLocations (join: company_location_user -> company_locations)
                            $names = $record->companyLocations?->pluck('name')->filter()->all() ?? [];

                            return count($names) ? implode(', ', $names) : null;
                        })
                        ->placeholder('Belum diset')
                        ->icon('heroicon-o-map-pin'),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
            ->filters([
                    SelectFilter::make('role')
                        ->options([
                                'admin' => 'Admin',
                                'kepala_lembaga' => 'Pimpinan Yayasan',
                                'manager' => 'Kepala Bagian / Kepala Sekolah',
                                'kepala_sub_bagian' => 'Kepala Sub Bagian',
                                'employee' => 'Employee',
                            ]),
                    SelectFilter::make('departemen_id')
                        ->label('Departemen')
                        ->options(function () {
                            $base = \App\Models\Departemen::query()->orderBy('name');
                            if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
                                $base->whereKey(auth()->user()->departemen_id);
                            }

                            return $base->pluck('name', 'id')->toArray();
                        })
                        ->preload()
                        ->searchable()
                        ->query(function (Builder $query, array $data) {
                            $id = $data['value'] ?? null;
                            if ($id && \App\Models\Departemen::whereKey($id)->exists()) {
                                $query->where('departemen_id', $id);
                            }
                        }),
                ])
            ->headerActions([
                    Action::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (\Livewire\Component $livewire) {
                            return $livewire->exportUsersExcel();
                        }),
                    Action::make('export_pdf')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->action(function (\Livewire\Component $livewire) {
                            return $livewire->exportUsersPdf();
                        }),
                ])
            ->recordActions([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(function ($record) {
                            if (!auth()->check()) {
                                return true;
                            }
                            $role = auth()->user()->role;
                            if ($role === 'employee') {
                                return auth()->id() === ($record->id ?? null);
                            }
                            if (in_array($role, ['manager', 'kepala_sub_bagian'], true)) {
                                return (auth()->user()->departemen_id ?? null) === ($record->departemen_id ?? null);
                            }

                            return true;
                        }),
                    Action::make('lihat_jadwal')
                        ->label('Lihat Jadwal')
                        ->icon('heroicon-o-eye')
                        ->visible(function () {
                            return auth()->check() && auth()->user()->role === 'admin';
                        })
                        ->form([
                                Select::make('month')
                                    ->label('Bulan')
                                    ->options([
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ])
                                    ->required()
                                    ->default((int) now()->format('n')),
                                TextInput::make('year')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(2100)
                                    ->required()
                                    ->default((int) now()->format('Y')),
                            ])
                        ->action(function (array $data, $record) {
                            $userId = $record->id;
                            $month = (int) ($data['month'] ?? (int) now()->format('n'));
                            $year = (int) ($data['year'] ?? (int) now()->format('Y'));

                            $schedule = self::retrieveEmployeeWorkSchedule($userId, $month, $year);

                            if (!$schedule) {
                                Notification::make()
                                    ->title('Jadwal tidak ditemukan')
                                    ->body("Belum ada jadwal untuk bulan {$month} tahun {$year}.")
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $allowedDays = self::normalizeAllowedDaysArray((array) ($schedule->allowed_days ?? []));
                            $trueDays = [];
                            $falseDays = [];

                            foreach ($allowedDays as $day => $value) {
                                $isTrue = $value === true;
                                if ($isTrue) {
                                    $trueDays[] = $day;
                                } else {
                                    $falseDays[] = $day;
                                }
                            }

                            $summary = 'TRUE: ' . (empty($trueDays) ? '-' : implode(', ', $trueDays))
                                . ' | FALSE: ' . (empty($falseDays) ? '-' : implode(', ', $falseDays));

                            Log::info('audit:employee_work_schedule.viewed', [
                                'actor_id' => auth()->id(),
                                'user_id' => $userId,
                                'month' => $month,
                                'year' => $year,
                                'allowed_days' => $allowedDays,
                            ]);

                            Notification::make()
                                ->title("Jadwal bulan {$month} / {$year}")
                                ->body($summary)
                                ->info()
                                ->send();
                        }),

                    Action::make('ubah_jadwal')
                        ->label('Kirim')
                        ->icon('heroicon-o-calendar')
                        ->visible(function () {
                            return auth()->check() && auth()->user()->role === 'admin';
                        })
                        ->form([
                                Select::make('month')
                                    ->label('Bulan')
                                    ->options([
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ])
                                    ->required()
                                    ->default((int) now()->format('n'))
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, Get $get) {
                                        self::syncAllowedDaysField($set, $get);
                                    }),
                                TextInput::make('year')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(2100)
                                    ->required()
                                    ->default((int) now()->format('Y'))
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, Get $get) {
                                        self::syncAllowedDaysField($set, $get);
                                    }),
                                TextInput::make('user_id_internal')
                                    ->hidden()
                                    ->default(function ($record) {
                                        return $record->id ?? null;
                                    }),
                                Select::make('shift_id')
                                    ->label('Shift Kerja')
                                    ->options(function () {
                                        return ShiftKerja::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, Get $get) {
                                        self::syncAllowedDaysField($set, $get);
                                    }),
                                CheckboxList::make('allowed_days')
                                    ->label('Hari Diizinkan')
                                    ->hintActions([
                                            Action::make('check_all')
                                                ->label('Check All')
                                                ->icon('heroicon-o-check-circle')
                                                ->action(function ($set, Get $get) {
                                                    $month = (int) ($get('month') ?? (int) now()->format('n'));
                                                    $year = (int) ($get('year') ?? (int) now()->format('Y'));
                                                    $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                                                    $all = [];
                                                    for ($d = 1; $d <= $daysInMonth; $d++) {
                                                        $all[] = (string) $d;
                                                    }
                                                    $set('allowed_days', $all);
                                                }),
                                            Action::make('uncheck_all')
                                                ->label('Uncheck All')
                                                ->icon('heroicon-o-x-circle')
                                                ->action(function ($set) {
                                                    $set('allowed_days', []);
                                                }),
                                            Action::make('sabtu_libur')
                                                ->label('Sabtu Libur')
                                                ->icon('heroicon-o-minus-circle')
                                                ->action(function ($set, Get $get) {
                                                    $month = (int) ($get('month') ?? (int) now()->format('n'));
                                                    $year = (int) ($get('year') ?? (int) now()->format('Y'));
                                                    $current = $get('allowed_days') ?? [];

                                                    $newAllowed = [];
                                                    foreach ($current as $day) {
                                                        $date = \Carbon\Carbon::createFromDate($year, $month, (int) $day);
                                                        if ($date->dayOfWeek !== 6) {
                                                            $newAllowed[] = (string) $day;
                                                        }
                                                    }
                                                    $set('allowed_days', $newAllowed);
                                                }),
                                        ])
                                    ->columns(7)
                                    ->options(function (Get $get): array {
                                        $month = (int) ($get('month') ?? (int) now()->format('n'));
                                        $year = (int) ($get('year') ?? (int) now()->format('Y'));
                                        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                                        $options = [];
                                        for ($d = 1; $d <= $daysInMonth; $d++) {
                                            $date = \Carbon\Carbon::createFromDate($year, $month, $d);
                                            $dow = $date->dayOfWeek;
                                            $label = $date->translatedFormat('D d');
                                            if ($dow === 0) {
                                                $label = 'Minggu ' . $date->format('d');
                                                $label = '<span class="text-red-600 font-semibold">' . $label . '</span>';
                                            }
                                            $options[(string) $d] = $label;
                                        }

                                        return $options;
                                    })
                                    ->default(function (Get $get): array {
                                        $month = (int) ($get('month') ?? (int) now()->format('n'));
                                        $year = (int) ($get('year') ?? (int) now()->format('Y'));

                                        return self::buildDefaultAllowedDaysSelection($year, $month);
                                    })
                                    ->allowHtml()
                                    ->reactive()
                                    ->required(),
                            ])
                        ->action(function (array $data, $record) {
                            $userId = $record->id;
                            $month = (int) ($data['month'] ?? (int) now()->format('n'));
                            $year = (int) ($data['year'] ?? (int) now()->format('Y'));
                            $selected = self::extractSelectedDayKeys((array) ($data['allowed_days'] ?? []));
                            $shiftId = isset($data['shift_id']) ? (int) $data['shift_id'] : null;

                            $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

                            $newMap = [];
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                $date = \Carbon\Carbon::createFromDate($year, $month, $d);
                                if ($date->dayOfWeek === 0) {
                                    $newMap[(string) $d] = false;

                                    continue;
                                }
                                $newMap[(string) $d] = in_array((string) $d, $selected, true);
                            }

                            try {
                                DB::transaction(function () use ($userId, $month, $year, $newMap, $shiftId) {
                                    $schedule = EmployeeWorkSchedule::query()
                                        ->where('user_id', $userId)
                                        ->where('month', $month)
                                        ->where('year', $year)
                                        ->where('shift_id', $shiftId)
                                        ->first();

                                    if (!$schedule) {
                                        EmployeeWorkSchedule::query()->create([
                                            'user_id' => $userId,
                                            'shift_id' => $shiftId,
                                            'month' => $month,
                                            'year' => $year,
                                            'allowed_days' => $newMap,
                                        ]);
                                    } else {
                                        $schedule->allowed_days = $newMap;
                                        if ($schedule->isDirty('allowed_days')) {
                                            $schedule->save();
                                        }
                                    }
                                });

                                $retrievedSchedule = self::retrieveEmployeeWorkSchedule($userId, $month, $year);

                                if (!$retrievedSchedule) {
                                    Notification::make()
                                        ->title('Jadwal tersimpan, tetapi gagal mengambil data')
                                        ->body('Data jadwal tidak ditemukan setelah penyimpanan.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $allowedDays = self::normalizeAllowedDaysArray((array) ($retrievedSchedule->allowed_days ?? []));
                                $formatted = json_encode($allowedDays);

                                Log::info('audit:employee_work_schedule.updated', [
                                    'actor_id' => auth()->id(),
                                    'user_id' => $userId,
                                    'month' => $month,
                                    'year' => $year,
                                    'shift_id' => $shiftId,
                                    'allowed_days' => $allowedDays,
                                ]);

                                Notification::make()
                                    ->title('Jadwal kerja berhasil disimpan')
                                    ->body('allowed_days: ' . $formatted)
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Log::error('audit:employee_work_schedule.update_failed', [
                                    'actor_id' => auth()->id(),
                                    'user_id' => $userId,
                                    'month' => $month,
                                    'year' => $year,
                                    'error' => $e->getMessage(),
                                ]);
                                Notification::make()
                                    ->title('Gagal menyimpan jadwal')
                                    ->body('Terjadi kesalahan saat menyimpan data.')
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
            ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->visible(function () {
                                if (!auth()->check()) {
                                    return false;
                                }

                                // return ! in_array(auth()->user()->role, ['employee'], true);
                                return auth()->user()->role === 'admin';
                            }),
                        BulkAction::make('reset_registration')
                            ->label('Reset pendaftaran')
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->visible(function () {
                                return auth()->check() && auth()->user()->role === 'admin';
                            })
                            ->action(function (Collection $records) {
                                if ($records->isEmpty()) {
                                    Notification::make()
                                        ->title('Tidak ada data dipilih')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $allowedRoles = ['employee', 'manager', 'kepala_sub_bagian'];

                                $allowed = $records->filter(
                                    fn($user) => in_array($user->role, $allowedRoles, true)
                                );

                                if ($allowed->isEmpty()) {
                                    Notification::make()
                                        ->title('Tidak ada pengguna yang dapat di-reset')
                                        ->warning()
                                        ->body('Reset pendaftaran hanya berlaku untuk pengguna dengan role Employee, Manager, atau Kepala Sub Bagian.')
                                        ->send();

                                    return;
                                }

                                try {
                                    DB::transaction(function () use ($allowed) {
                                        foreach ($allowed as $user) {
                                            $user->update(['face_embedding' => null]);
                                        }
                                    });
                                    Log::info('audit:user.reset_registration', [
                                        'actor_id' => auth()->id(),
                                        'user_ids' => $allowed->pluck('id')->all(),
                                    ]);
                                    Notification::make()
                                        ->title('Reset pendaftaran berhasil')
                                        ->body('Pendaftaran wajah untuk pengguna terpilih telah di-reset.')
                                        ->success()
                                        ->send();
                                } catch (\Throwable $e) {
                                    Log::error('audit:user.reset_registration_failed', [
                                        'actor_id' => auth()->id(),
                                        'message' => $e->getMessage(),
                                    ]);
                                    Notification::make()
                                        ->title('Reset pendaftaran gagal')
                                        ->body('Terjadi kesalahan saat memproses data.')
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),
                ])
            // ->defaultSort('jabatan_id', direction: 'asc');
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->orderBy('jabatan_id', 'asc'));
    }

    protected static function retrieveEmployeeWorkSchedule(int $userId, int $month, int $year): ?EmployeeWorkSchedule
    {
        return EmployeeWorkSchedule::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    protected static function extractSelectedDayKeys(array $selected): array
    {
        if ($selected === []) {
            return [];
        }

        $keys = array_keys($selected);
        $isList = $keys === array_keys($keys);

        $days = [];

        if ($isList) {
            foreach ($selected as $value) {
                $value = (string) $value;
                if (ctype_digit($value)) {
                    $days[] = ltrim($value, '0');
                }
            }
        } else {
            foreach ($selected as $day => $value) {
                $dayStr = (string) $day;
                if (!ctype_digit($dayStr)) {
                    continue;
                }

                $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool === true) {
                    $days[] = ltrim($dayStr, '0');
                }
            }
        }

        return array_values(array_unique($days));
    }

    protected static function normalizeAllowedDaysArray(array $raw): array
    {
        $normalized = [];

        foreach ($raw as $key => $value) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalized[(string) $key] = $bool === true;
        }

        return $normalized;
    }

    protected static function getScheduleFromCacheForForm(int $userId, int $month, int $year, int $shiftId): ?EmployeeWorkSchedule
    {
        $cacheKey = "employee_work_schedule:{$userId}:{$year}:{$month}:{$shiftId}";

        try {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes(5),
                static fn() => EmployeeWorkSchedule::query()
                    ->where('user_id', $userId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('shift_id', $shiftId)
                    ->first()
            );
        } catch (\Throwable $e) {
            Log::error('employee_work_schedule.fetch_failed', [
                'actor_id' => auth()->id(),
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'shift_id' => $shiftId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected static function syncAllowedDaysField(callable $set, Get $get): void
    {
        $month = (int) ($get('month') ?? (int) now()->format('n'));
        $year = (int) ($get('year') ?? (int) now()->format('Y'));
        $userId = (int) ($get('user_id_internal') ?? 0);
        $shiftId = (int) ($get('shift_id') ?? 0);

        if ($userId > 0 && $shiftId > 0) {
            $schedule = self::getScheduleFromCacheForForm($userId, $month, $year, $shiftId);

            if ($schedule) {
                $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $raw = (array) ($schedule->allowed_days ?? []);
                $validated = self::validateAllowedDaysForForm($raw, $daysInMonth);

                $selected = [];
                foreach ($validated as $day => $isAllowed) {
                    if ($isAllowed === true) {
                        $selected[] = (string) $day;
                    }
                }

                $set('allowed_days', $selected);

                return;
            }
        }

        $set('allowed_days', self::buildDefaultAllowedDaysSelection($year, $month));
    }

    protected static function buildDefaultAllowedDaysSelection(int $year, int $month): array
    {
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $selected = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = \Carbon\Carbon::createFromDate($year, $month, $d);
            if ($date->dayOfWeek === 0) {
                continue;
            }
            $selected[] = (string) $d;
        }

        return $selected;
    }

    protected static function validateAllowedDaysForForm(array $raw, int $daysInMonth): array
    {
        $normalized = [];

        foreach ($raw as $day => $value) {
            $dayStr = (string) $day;

            if (!ctype_digit($dayStr)) {
                continue;
            }

            $dayInt = (int) $dayStr;

            if ($dayInt < 1 || $dayInt > $daysInMonth) {
                continue;
            }

            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalized[(string) $dayInt] = $bool === true;
        }

        return $normalized;
    }
}
