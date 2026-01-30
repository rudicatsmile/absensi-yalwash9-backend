<?php

namespace App\Filament\Imports;

use App\Models\JamKerja;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class JamKerjaImporter extends Importer
{
    protected static ?string $model = JamKerja::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('start_time')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('end_time')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('is_cross_day')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('grace_period_minutes')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('description'),
        ];
    }

    public function resolveRecord(): JamKerja
    {
        return new JamKerja();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your jam kerja import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
