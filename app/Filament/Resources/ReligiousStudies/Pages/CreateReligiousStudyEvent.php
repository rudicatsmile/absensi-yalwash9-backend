<?php

namespace App\Filament\Resources\ReligiousStudies\Pages;

use App\Filament\Resources\ReligiousStudies\ReligiousStudyEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReligiousStudyEvent extends CreateRecord
{
    protected static string $resource = ReligiousStudyEventResource::class;

    public function getTitle(): string
    {
        return 'Tambah Data';
    }

    public function getHeading(): string
    {
        return 'Tambah Data';
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}