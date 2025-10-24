<?php

namespace App\Filament\Resources\MeetingTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meeting Type Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        TextInput::make('quota_days')
                            ->label('Quota Days')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Enter the number of days allowed per year. Use 0 for unlimited.'),

                        Toggle::make('is_paid')
                            ->label('Is Paid Meeting')
                            ->default(true)
                            ->helperText('Check if this meeting type is paid'),
                    ])
                    ->columns(2),
            ]);
    }
}