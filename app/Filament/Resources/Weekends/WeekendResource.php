<?php

namespace App\Filament\Resources\Weekends;

use App\Filament\Resources\Weekends\Pages\ListWeekends;
use App\Filament\Resources\Weekends\Tables\WeekendsTable;
use App\Models\Holiday;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class WeekendResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static ?string $pluralLabel = 'Hari Libur';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('type', Holiday::TYPE_WEEKEND);
    }

    public static function table(Table $table): Table
    {
        return WeekendsTable::configure($table);
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
            'index' => ListWeekends::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && ! in_array(auth()->user()->role, ['employee','manager','kepala_sub_bagian'], true);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && ! in_array(auth()->user()->role, ['employee','manager','kepala_sub_bagian'], true);
    }
}
