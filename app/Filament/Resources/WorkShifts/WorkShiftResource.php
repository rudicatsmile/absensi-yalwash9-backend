<?php

namespace App\Filament\Resources\WorkShifts;

use App\Filament\Resources\WorkShifts\Pages\CreateWorkShift;
use App\Filament\Resources\WorkShifts\Pages\EditWorkShift;
use App\Filament\Resources\WorkShifts\Pages\ListWorkShifts;
use App\Filament\Resources\WorkShifts\Schemas\WorkShiftForm;
use App\Filament\Resources\WorkShifts\Tables\WorkShiftsTable;
use App\Models\WorkShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkShiftResource extends Resource
{
    protected static ?string $model = WorkShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Work Shifts';

    protected static ?string $modelLabel = 'Work Shift';

    protected static ?string $pluralModelLabel = 'Work Shifts';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return WorkShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkShiftsTable::configure($table);
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
            'index' => ListWorkShifts::route('/'),
            'create' => CreateWorkShift::route('/create'),
            'edit' => EditWorkShift::route('/{record}/edit'),
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
