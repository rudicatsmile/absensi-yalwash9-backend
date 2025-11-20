<?php

namespace App\Filament\Resources\Meetings\Schemas;

use App\Models\MeetingType;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Meeting')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->options(\App\Models\User::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->required(),

                        Select::make('meeting_type_id')
                            ->label('Jenis Meeting')
                            ->relationship('meetingType', 'name')
                            ->options(MeetingType::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('date')
                            ->label('Tanggal Meeting')
                            ->required(),

                        TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->required(),

                        TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->required(),

                        Textarea::make('reason')
                            ->label('Alasan/Agenda Meeting')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Dokumen Pendukung')
                    ->schema([
                        FileUpload::make('document')
                            ->label('Dokumen PDF (Agenda, Undangan, dll)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('meeting-documents')
                            ->visibility('private')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Status Persetujuan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu Persetujuan',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->required()
                            ->reactive(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan (opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($operation) => $operation === 'edit'),
            ]);
    }
}