<?php

namespace App\Filament\Resources\Overtimes\Pages;

use App\Filament\Resources\Overtimes\OvertimeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOvertime extends CreateRecord
{
    protected static string $resource = OvertimeResource::class;

    public function mount(): void
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager','kepala_sub_bagian','employee'], true)) {
            \Log::info('audit:overtime.create.blocked', ['actor' => auth()->id()]);
            if (request()->expectsJson()) {
                response(['message' => 'Akses ditolak: Over Shift hanya dapat dibaca'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Over Shift hanya dapat dibaca');
        }
        parent::mount();
    }
}
