<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    public function mount(int|string $record): void
    {
        if (auth()->check() && auth()->user()->role === 'employee') {
            $req = request();
            if ($req->expectsJson()) {
                response(['message' => 'Akses ditolak: Menu ini khusus admin'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Menu ini khusus admin');
        }
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
