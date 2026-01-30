<?php

namespace App\Filament\Resources\JamKerjas;

use App\Filament\Resources\JamKerjas\Pages\CreateJamKerja;
use App\Filament\Resources\JamKerjas\Pages\EditJamKerja;
use App\Filament\Resources\JamKerjas\Pages\ListJamKerjas;
use App\Filament\Resources\JamKerjas\Schemas\JamKerjaForm;
use App\Filament\Resources\JamKerjas\Tables\JamKerjasTable;
use App\Models\JamKerja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JamKerjaResource extends Resource
{
    protected static ?string $model = JamKerja::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Jam Kerja';

    public static function form(Schema $schema): Schema
    {
        return JamKerjaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JamKerjasTable::configure($table);
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
            'index' => ListJamKerjas::route('/'),
            'create' => CreateJamKerja::route('/create'),
            'edit' => EditJamKerja::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee', 'manager', 'kepala_sub_bagian'], true);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee', 'manager', 'kepala_sub_bagian'], true);
    }
}
