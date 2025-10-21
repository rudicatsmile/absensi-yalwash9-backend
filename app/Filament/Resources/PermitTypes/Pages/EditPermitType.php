<?php

namespace App\Filament\Resources\PermitTypes\Pages;

use App\Filament\Resources\PermitTypes\PermitTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermitType extends EditRecord
{
    protected static string $resource = PermitTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
