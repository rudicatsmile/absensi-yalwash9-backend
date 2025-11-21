<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrated(fn($state) => filled($state))
                    ->minLength(8),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),
                Select::make('role')
                    ->label('Tipe user')
                    ->options([
                        'admin' => 'Admin',
                        'kepala_lembaga' => 'Pimpinan Yayasan',
                        'manager' => 'Kepala Bagian / Kepala Sekolah',
                        'kepala_sub_bagian' => 'Kepala Sub Bagian',
                        'employee' => 'Pegawai',
                    ])
                    ->required()
                    ->default('employee'),
                Select::make('jabatan_id')
                    ->label('Jabatan')
                    ->relationship('jabatan', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 jabatan untuk karyawan'),
                Select::make('departemen_id')
                    ->label('Unit Kerja')
                    ->relationship('departemen', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 departemen untuk karyawan'),
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
