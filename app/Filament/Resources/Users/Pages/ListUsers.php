<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager'], true)) {
            $actions[] = CreateAction::make();
        }
        return $actions;
    }
}
