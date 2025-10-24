<?php

namespace App\Filament\Resources\MeetingTypes\Pages;

use App\Filament\Resources\MeetingTypes\MeetingTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeetingType extends EditRecord
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}