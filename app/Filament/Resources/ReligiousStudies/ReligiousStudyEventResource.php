<?php

namespace App\Filament\Resources\ReligiousStudies;

use App\Filament\Resources\ReligiousStudies\Pages\CreateReligiousStudyEvent;
use App\Filament\Resources\ReligiousStudies\Pages\EditReligiousStudyEvent;
use App\Filament\Resources\ReligiousStudies\Pages\ListReligiousStudyEvents;
use App\Filament\Resources\ReligiousStudies\Schemas\ReligiousStudyEventForm;
use App\Filament\Resources\ReligiousStudies\Tables\ReligiousStudyEventsTable;
use App\Models\ReligiousStudyEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ReligiousStudyEventResource extends Resource
{
    protected static ?string $model = ReligiousStudyEvent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static UnitEnum|string|null $navigationGroup = 'Management Rapat';

    protected static ?string $navigationLabel = 'Event Notifikasi';

    protected static ?string $pluralLabel = 'Event Notifikasi';


    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return ReligiousStudyEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReligiousStudyEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReligiousStudyEvents::route('/'),
            'create' => CreateReligiousStudyEvent::route('/create'),
            'edit' => EditReligiousStudyEvent::route('/{record}/edit'),
        ];
    }
}
