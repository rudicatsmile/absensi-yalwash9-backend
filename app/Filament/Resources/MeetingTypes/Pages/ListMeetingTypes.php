<?php

namespace App\Filament\Resources\MeetingTypes\Pages;

use App\Filament\Resources\MeetingTypes\MeetingTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeetingTypes extends ListRecords
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Meeting Type'),
        ];
    }
}