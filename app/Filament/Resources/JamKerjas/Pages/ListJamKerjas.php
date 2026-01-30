<?php

namespace App\Filament\Resources\JamKerjas\Pages;

use App\Filament\Resources\JamKerjas\JamKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\JamKerjaExporter;
use App\Filament\Imports\JamKerjaImporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;

class ListJamKerjas extends ListRecords
{
    protected static string $resource = JamKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(JamKerjaImporter::class),
            ExportAction::make()
                ->exporter(JamKerjaExporter::class),
            CreateAction::make(),
        ];
    }
}
