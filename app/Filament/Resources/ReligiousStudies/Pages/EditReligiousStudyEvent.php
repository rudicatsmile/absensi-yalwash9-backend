<?php

namespace App\Filament\Resources\ReligiousStudies\Pages;

use App\Filament\Resources\ReligiousStudies\ReligiousStudyEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReligiousStudyEvent extends EditRecord
{
    protected static string $resource = ReligiousStudyEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Ubah Data';
    }

    public function getHeading(): string
    {
        return 'Ubah Data';
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}