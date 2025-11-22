<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager','kepala_sub_bagian'], true)) {
            $target = \App\Models\User::find($record);
            if ($target && ($target->departemen_id ?? null) !== (auth()->user()->departemen_id ?? null)) {
                \Log::info('audit:user.edit.blocked', ['actor' => auth()->id(), 'target' => $record]);
                if (request()->expectsJson()) {
                    response(['message' => 'Akses ditolak: Anda hanya dapat mengedit pegawai di departemen Anda'], 403)->send();
                    exit;
                }
                abort(403, 'Akses ditolak: Anda hanya dapat mengedit pegawai di departemen Anda');
            }
        }
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function () {
                    if (!auth()->check()) return false;
                    $role = auth()->user()->role;
                    if ($role === 'employee') return false;
                    if (in_array($role, ['manager','kepala_sub_bagian'], true)) {
                        return (auth()->user()->departemen_id ?? null) === ($this->record->departemen_id ?? null);
                    }
                    return true;
                }),
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
