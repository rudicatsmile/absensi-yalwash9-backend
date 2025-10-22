<?php

namespace App\Filament\Resources\Permits;

use App\Filament\Resources\Permits\Pages\CreatePermit;
use App\Filament\Resources\Permits\Pages\EditPermit;
use App\Filament\Resources\Permits\Pages\ListPermits;
use App\Filament\Resources\Permits\Pages\ViewPermit;
use App\Filament\Resources\Permits\Schemas\PermitForm;
use App\Filament\Resources\Permits\Tables\PermitsTable;
use App\Models\Permit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PermitResource extends Resource
{
    protected static ?string $model = Permit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Management Izin';

    protected static ?string $navigationLabel = 'Izin Kerja';

    protected static ?string $pluralLabel = 'Izin Kerja';

    protected static ?int $navigationSort = 43;

    public static function form(Schema $schema): Schema
    {
        return PermitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermits::route('/'),
            'create' => CreatePermit::route('/create'),
            'view' => ViewPermit::route('/{record}'),
            'edit' => EditPermit::route('/{record}/edit'),
        ];
    }
}
