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
        if (!auth()->check() || auth()->user()->role !== 'employee') {
            $actions[] = CreateAction::make();
        }
        return $actions;
    }
}
