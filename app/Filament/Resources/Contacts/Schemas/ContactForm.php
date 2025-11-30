<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('avatarUrl')
                ->label('Avatar')
                ->image()
                ->directory('contacts')
                ->disk('public')
                ->imagePreviewHeight('100')
                ->loadingIndicatorPosition('left')
                ->panelAspectRatio('2:1')
                ->panelLayout('integrated')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->maxSize(2048) // 2MB
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif']),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->tel()
                ->required()
                ->maxLength(255),
            TextInput::make('bagian')
                ->nullable()
                ->maxLength(255),
            TextInput::make('sub_bagian')
                ->nullable()
                ->maxLength(255),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                ])
                ->required(),
        ]);
    }
}
