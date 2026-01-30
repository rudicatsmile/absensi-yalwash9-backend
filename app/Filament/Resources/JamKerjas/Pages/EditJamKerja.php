<?php

namespace App\Filament\Resources\JamKerjas\Pages;

use App\Filament\Resources\JamKerjas\JamKerjaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJamKerja extends EditRecord
{
    protected static string $resource = JamKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
