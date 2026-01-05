<?php

namespace App\Filament\Resources\ManualAttendances\Pages;

use App\Filament\Resources\ManualAttendances\ManualAttendanceResource;
use Filament\Resources\Pages\ListRecords;

class ListManualAttendances extends ListRecords
{
    protected static string $resource = ManualAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

