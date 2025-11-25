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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['image_upload']) && $data['image_upload']) {
            $value = $data['image_upload'];
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }
            if (is_string($value) && $value !== '') {
                $data['image_path'] = $value;
            }
            unset($data['image_upload']);
        }

        return $data;
    }
}
