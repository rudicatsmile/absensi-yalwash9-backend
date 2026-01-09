<?php

namespace App\Filament\Resources\MeetingTypes;

use App\Filament\Resources\MeetingTypes\Pages\CreateMeetingType;
use App\Filament\Resources\MeetingTypes\Pages\EditMeetingType;
use App\Filament\Resources\MeetingTypes\Pages\ListMeetingTypes;
use App\Filament\Resources\MeetingTypes\Schemas\MeetingTypeForm;
use App\Filament\Resources\MeetingTypes\Tables\MeetingTypesTable;
use App\Models\MeetingType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MeetingTypeResource extends Resource
{
    protected static ?string $model = MeetingType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static UnitEnum|string|null $navigationGroup = 'Management Rapat';

    protected static ?string $navigationLabel = 'Tipe Rapat';

    protected static ?string $pluralLabel = 'Tipe Rapat';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return MeetingTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingTypesTable::configure($table);
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
            'index' => ListMeetingTypes::route('/'),
            'create' => CreateMeetingType::route('/create'),
            'edit' => EditMeetingType::route('/{record}/edit'),
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
