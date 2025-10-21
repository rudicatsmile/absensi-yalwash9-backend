<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\ShiftKerja;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Yayasan')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        TextEntry::make('address')
                            ->label('ALamat')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Setting Lokasi')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('locations')
                            ->label('Lokasi')
                            ->icon('heroicon-o-map')
                            ->state(function ($record) {
                                return $record->locations
                                    ->map(function ($loc, $index) {
                                        $name = $loc->name ?: 'Lokasi ' . ($index + 1);
                                        $addr = $loc->address ? ' — ' . $loc->address : '';
                                        $attendance_type = $loc->attendance_type ? '  ' . $loc->attendance_type : '';
                                        $lat = rtrim(rtrim(number_format((float) $loc->latitude, 6, '.', ''), '0'), '.');
                                        $lng = rtrim(rtrim(number_format((float) $loc->longitude, 6, '.', ''), '0'), '.');
                                        $rad = rtrim(rtrim(number_format((float) $loc->radius_km, 2, '.', ''), '0'), '.');

                                        // return sprintf(
                                        //     '%s: lat %s, long %s (radius %s km)%s',
                                        //     $name,
                                        //     $lat,
                                        //     $lng,
                                        //     $rad,
                                        //     $addr
                                        // );
                        
                                        return sprintf(
                                            '%s:  (radius %s km)  %s',
                                            $name,
                                            $rad,
                                            $attendance_type
                                        );
                                    })
                                    ->toArray();
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No locations configured')
                            ->columnSpanFull(),

                        //TextEntry::make('attendance_type')
                        //->label('Metode Presensi')
                        // ->badge()
                        // ->formatStateUsing(fn(string $state): string => match ($state) {
                        //     'location_based_only' => 'Location Based (GPS)',
                        //     'face_recognition_only' => 'Face Recognition',
                        //     'hybrid' => 'Hybrid (GPS + Face)',
                        //     default => $state,
                        // })
                        // ->color(fn(string $state): string => match ($state) {
                        //     'location_based_only' => 'primary',
                        //     'face_recognition_only' => 'success',
                        //     'hybrid' => 'warning',
                        //     default => 'gray',
                        // }),
                    ])
                    ->columns(4),

                Section::make('Shift Kerja')
                    ->description('Daftar shift kerja yang tersedia')
                    ->schema([
                        TextEntry::make('shifts')
                            ->label('Shift Kerja')
                            ->state(function () {
                                return ShiftKerja::where('is_active', true)
                                    ->orderBy('start_time')
                                    ->get()
                                    ->map(function ($shift) {
                                        $crossDay = $shift->is_cross_day ? ' 🌙' : '';
                                        $grace = $shift->grace_period_minutes . ' min grace';
                                        $employees = $shift->users()->count();

                                        return sprintf(
                                            '%s: %s - %s%s (%s, %d employees)',
                                            $shift->name,
                                            $shift->start_time->format('H:i'),
                                            $shift->end_time->format('H:i'),
                                            $crossDay,
                                            $grace,
                                            $employees
                                        );
                                    })
                                    ->toArray();
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No shifts configured'),
                    ])
                    ->collapsible(),
            ]);
    }
}
