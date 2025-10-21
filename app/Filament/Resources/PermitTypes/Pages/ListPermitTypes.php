<?php

namespace App\Filament\Resources\PermitTypes\Pages;

use App\Filament\Resources\PermitTypes\PermitTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermitTypes extends ListRecords
{
    protected static string $resource = PermitTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
