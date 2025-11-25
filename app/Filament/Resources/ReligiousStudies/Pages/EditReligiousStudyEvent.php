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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['image_upload']) && $data['image_upload']) {
            $value = $data['image_upload'];
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }
            if (is_string($value) && $value !== '') {
                $data['image_path'] = $value;
            }
        }
        unset($data['image_upload']);

        return $data;
    }
}
