<?php

namespace App\Filament\Resources\ReligiousStudies\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReligiousStudyEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Notifikasi')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('title')->label('Judul')->required(),
                            TextInput::make('location')->label('Lokasi')->required(),
                            TextInput::make('theme')->label('Tema')->required(),
                            TextInput::make('speaker')->label('Pemateri')->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            DateTimePicker::make('event_at')->label('Waktu Pengajian')->required(),
                            DateTimePicker::make('notify_at')->label('Waktu Kirim Notifikasi')->required(),
                            // Multi-select Departemen (opsional)
                            Select::make('departemen_ids')
                                ->label('Departemen')
                                ->searchable()
                                ->preload()
                                ->multiple()
                                ->options(\App\Models\Departemen::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->placeholder('Pilih satu/lebih departemen (opsional)')
                                ->rules(['nullable'])
                                ->default(null),
                            // Multi-select Jabatan (opsional)
                            Select::make('jabatan_ids')
                                ->label('Jabatan')
                                ->searchable()
                                ->preload()
                                ->multiple()
                                ->options(\App\Models\Jabatan::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->placeholder('Pilih satu/lebih jabatan (opsional)')
                                ->rules(['nullable'])
                                ->default(null),
                        ]),

                    Textarea::make('message')->label('Pesan')->rows(3)->columnSpanFull(),
                    Toggle::make('cancelled')->label('Dibatalkan'),
                    Toggle::make('isoverlay')->label('Tampilkan Overlay'),
                    

                    FileUpload::make('image_upload')
                        ->label('Upload Gambar')
                        ->image()
                        ->directory('religious-study-events')
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png'])
                        ->maxSize(2048)
                        ->statePath('image_path')
                        ->openable()
                        ->downloadable(),
                ])
                ->columns(1),
        ]);
    }
}
