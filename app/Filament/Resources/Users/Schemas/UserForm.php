<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationAttribute('Username')
                    ->live(debounce: 500)
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationAttribute('Email')
                    ->live(debounce: 500)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(6)
                    ->maxLength(100)
                    // wajib diisi hanya ketika membuat data baru (halaman “create”), dan tidak wajib ketika mengedit (halaman “edit”).
                    ->required(fn(?string $context): bool => $context === 'create')
                    ->default('')                  // ← Pastikan default kosong

                    // Selalu kosongkan field password saat form pertama kali dimuat (baik create maupun edit).
                    ->placeholder('Masukkan password'),
                // TextInput::make('password')
                //     ->password()
                //     ->revealable()
                //     ->suffixIcon('heroicon-o-eye')
                //     ->required(fn(string $context): bool => $context === 'create')
                //     ->dehydrated(fn($state) => filled($state))
                //     ->validationAttribute('Password')
                //     ->live(onBlur: true)
                //     ->afterStateHydrated(fn($component) => $component->state(''))
                //     ->autocomplete('new-password')
                //     ->minLength(8)
                //     ->placeholder('Kosongkan jika tidak ingin mengubah')
                //     ->rules(function (Get $get) {
                //         $val = $get('password');
                //         if (!filled($val)) {
                //             return ['nullable'];
                //         }
                //         return ['min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'];
                //     })
                //     ->helperText(function (\Filament\Schemas\Components\Utilities\Get $get) {
                //         $val = $get('password');
                //         if (!filled($val)) {
                //             return 'Password tidak akan diubah jika dibiarkan kosong';
                //         }
                //         return 'Gunakan kombinasi huruf besar, huruf kecil, dan angka (min 8 karakter)';
                //     }),
                TextInput::make('phone')
                    ->tel()
                    ->live(debounce: 500)
                    ->rules(['nullable', 'regex:/^\+?[0-9\-\s]{7,20}$/'])
                    ->validationAttribute('Nomor telepon')
                    ->maxLength(20),
                Select::make('role')
                    ->label('Tipe user')
                    ->options(function () {
                        if (auth()->check()) {
                            $role = auth()->user()->role;
                            if ($role === 'manager') {
                                return [
                                    'kepala_sub_bagian' => 'Kepala Sub Bagian',
                                    'employee' => 'Pegawai',
                                ];
                            }
                            if ($role === 'kepala_sub_bagian') {
                                return [
                                    'employee' => 'Pegawai',
                                ];
                            }
                        }
                        return [
                            'admin' => 'Admin',
                            'kepala_lembaga' => 'Pimpinan Yayasan',
                            'manager' => 'Kepala Bagian / Kepala Sekolah',
                            'kepala_sub_bagian' => 'Kepala Sub Bagian',
                            'employee' => 'Pegawai',
                        ];
                    })
                    ->required()
                    ->disabled(fn(string $context) => $context === 'edit' && auth()->check() && auth()->user()->role === 'employee')
                    ->default('employee'),
                Select::make('jabatan_id')
                    ->label('Jabatan')
                    ->relationship('jabatan', 'name', modifyQueryUsing: function ($query) {
                        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
                            $query->whereIn('name', ['Kasubag', 'Pegawai']);
                        }
                        $query->orderBy('id');
                    })
                    ->required()
                    ->disabled(fn(string $context) => $context === 'edit' && auth()->check() && auth()->user()->role === 'employee')
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 jabatan untuk karyawan'),
                Select::make('departemen_id')
                    ->label('Unit Kerja')
                    ->options(function (Get $get) {
                        $base = \App\Models\Departemen::query()->orderBy('urut');
                        if (auth()->check()) {
                            $role = auth()->user()->role;
                            if (in_array($role, ['manager', 'kepala_sub_bagian'], true)) {
                                $base->whereKey(auth()->user()->departemen_id);
                            } elseif ($role === 'employee') {
                                $recordDept = $get('departemen_id') ?? (auth()->user()->departemen_id ?? null);
                                if ($recordDept) {
                                    $base->whereKey($recordDept);
                                } else {
                                    $base->whereRaw('1 = 0');
                                }
                            }
                        }
                        return $base->pluck('name', 'id')->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default(fn() => (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) ? auth()->user()->departemen_id : null)
                    ->disabled(condition: fn() => (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true) && !is_null(auth()->user()->departemen_id)) || (auth()->check() && auth()->user()->role === 'employee'))
                    ->dehydrated(true)
                    ->helperText(function (Get $get) {
                        if (auth()->check() && auth()->user()->role === 'employee') {
                            $recordDept = $get('departemen_id') ?? (auth()->user()->departemen_id ?? null);
                            if (is_null($recordDept)) {
                                return 'Tidak ada departemen terkait pada profil Anda. Hubungi admin untuk memperbarui profil.';
                            }
                        }
                        return 'Pilih 1 departemen untuk karyawan';
                    }),
                // Select::make('shift_kerja_id')
                //     ->label('Shift Kerja')
                //     ->relationship('shiftKerja', 'name')
                //     ->required()
                //     ->searchable()
                //     ->preload()
                //     ->helperText('Pilih 1 shift kerja untuk karyawan'),

                Select::make('shift_kerjas')
                    ->label('Shift Kerja')
                    ->relationship('shiftKerjas', 'name') // sinkron ke pivot
                    ->multiple()
                    ->disabled(fn(string $context) => $context === 'edit' && auth()->check() && in_array(auth()->user()->role, ['employee', 'kepala_sub_bagian'], true))
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih satu atau lebih shift kerja')
                    ->helperText('Anda dapat memilih beberapa shift kerja.')
                    ->rules(['required', 'array', 'min:1'])
                    ->validationAttribute('Shift Kerja')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Shift Kerja')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->editOptionForm([
                        TextInput::make('name')
                            ->label('Nama Shift Kerja')
                            ->required()
                            ->maxLength(255),
                    ]),

                Select::make('company_locations')
                    ->label('Lokasi')
                    ->relationship('companyLocations', 'name') // sinkron ke pivot
                    ->multiple()
                    ->disabled(fn(string $context) => $context === 'edit' && auth()->check() && in_array(auth()->user()->role, ['employee', 'kepala_sub_bagian'], true))
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih satu atau lebih lokasi')
                    ->helperText('Anda dapat memilih beberapa lokasi kerja.')
                    ->rules(['required', 'array', 'min:1'])
                    ->validationAttribute('Company Locations')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->editOptionForm([
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255),
                    ]),
                FileUpload::make('image_url')
                    ->label('Avatar')
                    ->image()
                    ->imageEditor()
                    ->directory('avatars')
                    ->visibility('public')
                    ->disk('public'),
                // ->columnSpanFull(),
                Textarea::make('face_embedding')
                    ->label('Face Embedding Data')
                    ->hidden()
                    ->columnSpanFull(),
                TextInput::make('fcm_token')
                    ->label('FCM Token')
                    ->hidden()
                    ->columnSpanFull(),
            ]);
    }
}
