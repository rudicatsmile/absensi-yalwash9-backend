<?php

namespace App\Filament\Resources\PermitTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PermitTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nama Jenis Izin')
                    ->searchable()
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('quota_days')
                    ->label('Kuota Hari')
                    ->numeric()
                    ->sortable(),
                
                \Filament\Tables\Columns\IconColumn::make('is_paid')
                    ->label('Dibayar')
                    ->boolean()
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('urut')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
