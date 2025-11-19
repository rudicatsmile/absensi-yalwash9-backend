<?php

namespace App\Filament\Resources\ReligiousStudies\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReligiousStudyEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Notifikasi')->schema([
                TextInput::make('title')->label('Judul')->required(),
                DateTimePicker::make('event_at')->label('Waktu Pengajian')->required(),
                DateTimePicker::make('notify_at')->label('Waktu Kirim Notifikasi')->required(),
                TextInput::make('location')->label('Lokasi')->required(),
                TextInput::make('theme')->label('Tema')->required(),
                TextInput::make('speaker')->label('Pemateri')->required(),
                Textarea::make('message')->label('Pesan')->rows(3),
                Toggle::make('cancelled')->label('Dibatalkan'),
            ])->columns(2),
        ]);
    }
}
