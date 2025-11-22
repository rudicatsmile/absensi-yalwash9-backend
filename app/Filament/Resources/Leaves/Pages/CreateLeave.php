<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    public function mount(): void
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager','kepala_sub_bagian','employee'], true)) {
            \Log::info('audit:leave.create.blocked', ['actor' => auth()->id()]);
            if (request()->expectsJson()) {
                response(['message' => 'Akses ditolak: Cuti hanya dapat dibaca'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Cuti hanya dapat dibaca');
        }
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
