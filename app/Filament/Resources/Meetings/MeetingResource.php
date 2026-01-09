<?php

namespace App\Filament\Resources\Meetings;

use App\Filament\Resources\Meetings\Pages\CreateMeeting;
use App\Filament\Resources\Meetings\Pages\EditMeeting;
use App\Filament\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Resources\Meetings\Pages\ViewMeeting;
use App\Filament\Resources\Meetings\Schemas\MeetingForm;
use App\Filament\Resources\Meetings\Tables\MeetingsTable;
use App\Models\Meeting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationLabel = 'Rapat-rapat';

    protected static ?string $modelLabel = 'Rapat';

    protected static ?string $pluralModelLabel = 'Rapat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Management Rapat';

    protected static ?int $navigationSort = 34;

    public static function form(Schema $schema): Schema
    {
        return MeetingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingsTable::configure($table);
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
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'view' => ViewMeeting::route('/{record}'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }

    // can create false
    public static function canCreate(): bool
    {
        return false;
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
