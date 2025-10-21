<?php

namespace App\Filament\Resources\Permits\Pages;

use App\Filament\Resources\Permits\PermitResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermit extends CreateRecord
{
    protected static string $resource = PermitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}