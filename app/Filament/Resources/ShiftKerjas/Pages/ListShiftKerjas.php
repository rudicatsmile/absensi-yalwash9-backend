<?php

namespace App\Filament\Resources\ShiftKerjas\Pages;

use App\Filament\Exports\ShiftKerjaExporter;
use App\Filament\Imports\ShiftKerjaImporter;
use App\Filament\Resources\ShiftKerjas\ShiftKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListShiftKerjas extends ListRecords
{
    protected static string $resource = ShiftKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ShiftKerjaImporter::class),
            ExportAction::make()
                ->exporter(ShiftKerjaExporter::class),
            CreateAction::make(),
        ];
    }
}
