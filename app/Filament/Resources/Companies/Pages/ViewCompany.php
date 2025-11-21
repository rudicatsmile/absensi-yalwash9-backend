<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function mount(int|string|null $record = null): void
    {
        if (auth()->check() && auth()->user()->role === 'employee') {
            $req = request();
            if ($req->expectsJson()) {
                response(['message' => 'Akses ditolak: Menu ini khusus admin'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Menu ini khusus admin');
        }
        // Jika tidak ada record yang diberikan, ambil company pertama
        if (! $record) {
            $company = Company::first();
            if ($company) {
                $record = $company->getKey();
            }
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
