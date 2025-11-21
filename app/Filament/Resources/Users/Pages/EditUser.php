<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->check() && auth()->user()->role !== 'employee'),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->record;

        // Sinkronisasi pivot: departemen_user, jabatan_user, shift_kerja_user
        if (! empty($user->departemen_id)) {
            $user->departemens()->sync([$user->departemen_id]);
        }
        if (! empty($user->jabatan_id)) {
            $user->jabatans()->sync([$user->jabatan_id]);
        }
        if (! empty($user->shift_kerja_id)) {
            $user->shiftKerjas()->sync([$user->shift_kerja_id]);
        }
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
