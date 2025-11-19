<?php

namespace App\Filament\Resources\ReligiousStudies\Pages;

use App\Filament\Resources\ReligiousStudies\ReligiousStudyEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReligiousStudyEvents extends ListRecords
{
    protected static string $resource = ReligiousStudyEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}