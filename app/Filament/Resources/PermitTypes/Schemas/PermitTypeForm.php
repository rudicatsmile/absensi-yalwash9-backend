<?php

namespace App\Filament\Resources\PermitTypes\Schemas;

use Filament\Schemas\Schema;

class PermitTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nama Jenis Izin')
                    ->required()
                    ->maxLength(250),
                
                \Filament\Forms\Components\TextInput::make('quota_days')
                    ->label('Kuota Hari')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(365),
                
                \Filament\Forms\Components\Toggle::make('is_paid')
                    ->label('Dibayar')
                    ->default(true),
                
                \Filament\Forms\Components\TextInput::make('urut')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->minValue(1),
            ]);
    }
}
