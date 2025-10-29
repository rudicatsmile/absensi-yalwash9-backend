<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('Address')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Location Settings')
                    ->description('Configure GPS location validation for attendance')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('locations')
                            ->label('Company Locations')
                            ->relationship('locations')
                            ->addActionLabel('Tambah Lokasi')
                            ->cloneable()
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(function (array $state): string {
                                return $state['name'] ?? 'Lokasi';
                            })
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Lokasi')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Kantor Pusat'),

                                Textarea::make('address')
                                    ->label('Alamat')
                                    ->rows(2)
                                    ->placeholder('Jl. Contoh No. 123, Jakarta')
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->required()
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90)
                                            ->placeholder('-6.200000')
                                            ->helperText('Koordinat lintang GPS'),

                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->required()
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180)
                                            ->placeholder('106.816666')
                                            ->helperText('Koordinat bujur GPS'),

                                        TextInput::make('radius_km')
                                            ->label('Radius (km)')
                                            ->required()
                                            ->numeric()
                                            ->default(0.05)
                                            ->step(0.01)
                                            ->minValue(0.01)
                                            ->maxValue(10)
                                            ->helperText('Radius check-in yang diizinkan'),
                                    ]),

                                Select::make('attendance_type')
                                    ->label('Attendance Method')
                                    ->required()
                                    ->options([
                                        'location_based_only' => 'Location Based Only (GPS)',
                                        'face_recognition_only' => 'Face Recognition Only',
                                        'hybrid' => 'Hybrid (GPS + Face Recognition)',
                                    ])
                                    ->default('location_based_only')
                                    ->helperText('Choose how employees check in/out')
                                    ->native(false)
                                    ->rules(['in:location_based_only,face_recognition_only,hybrid']),
                            ])
                            ->minItems(1)
                            ->defaultItems(1),
                    ]),
            ]);
    }
}
