<?php

namespace App\Filament\Resources\MeetingTypes\Pages;

use App\Filament\Resources\MeetingTypes\MeetingTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeetingType extends CreateRecord
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}