<?php

namespace App\Filament\Resources\PermitTypes;

use App\Filament\Resources\PermitTypes\Pages\CreatePermitType;
use App\Filament\Resources\PermitTypes\Pages\EditPermitType;
use App\Filament\Resources\PermitTypes\Pages\ListPermitTypes;
use App\Filament\Resources\PermitTypes\Schemas\PermitTypeForm;
use App\Filament\Resources\PermitTypes\Tables\PermitTypesTable;
use App\Models\PermitType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PermitTypeResource extends Resource
{
    protected static ?string $model = PermitType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Jenis Izin Kerja';

    protected static ?string $pluralLabel = 'Jenis Izin Kerja';

    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return PermitTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermitTypesTable::configure($table);
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
            'index' => ListPermitTypes::route('/'),
            'create' => CreatePermitType::route('/create'),
            'edit' => EditPermitType::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role !== 'employee';
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role !== 'employee';
    }
}
