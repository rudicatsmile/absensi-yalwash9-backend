<?php

namespace App\Filament\Resources\Permits\Pages;

use App\Filament\Resources\Permits\PermitResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermit extends CreateRecord
{
    protected static string $resource = PermitResource::class;

    public function mount(): void
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager','kepala_sub_bagian','employee'], true)) {
            \Log::info('audit:permit.create.blocked', ['actor' => auth()->id()]);
            if (request()->expectsJson()) {
                response(['message' => 'Akses ditolak: Izin Kerja hanya dapat dibaca'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Izin Kerja hanya dapat dibaca');
        }
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}