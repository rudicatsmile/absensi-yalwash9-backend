<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Work Shift')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Contoh: Shift Pagi, Shift Malam')
                    ->helperText('Nama unik untuk work shift ini'),

                TextInput::make('start_time')
                    ->label('Waktu Mulai')
                    ->type('time')
                    ->required()
                    ->placeholder('08:00')
                    ->helperText('Waktu mulai shift dalam format 24 jam'),

                TextInput::make('end_time')
                    ->label('Waktu Selesai')
                    ->type('time')
                    ->required()
                    ->placeholder('17:00')
                    ->helperText('Waktu selesai shift dalam format 24 jam'),

                Toggle::make('is_cross_day')
                    ->label('Lintas Hari')
                    ->default(false)
                    ->helperText('Aktifkan jika shift melewati tengah malam')
                    ->inline(false),

                TextInput::make('grace_period_minutes')
                    ->label('Grace Period (Menit)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(60)
                    ->placeholder('15')
                    ->helperText('Toleransi keterlambatan dalam menit (0-60)'),

                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->helperText('Work shift yang aktif dapat dipilih untuk karyawan')
                    ->inline(false),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder('Deskripsi tambahan untuk work shift ini...')
                    ->helperText('Deskripsi opsional (maksimal 500 karakter)')
                    ->columnSpanFull(),
            ]);
    }
}
