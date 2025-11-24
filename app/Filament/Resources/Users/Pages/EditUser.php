<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
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
                    if (!auth()->check())
                        return false;
                    $role = auth()->user()->role;
                    if ($role === 'employee')
                        return false;
                    if (in_array($role, ['manager', 'kepala_sub_bagian'], true)) {
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
        if (!empty($user->departemen_id)) {
            $user->departemens()->sync([$user->departemen_id]);
        }
        if (!empty($user->jabatan_id)) {
            $user->jabatans()->sync([$user->jabatan_id]);
        }
        if (!empty($user->shift_kerja_id)) {
            $user->shiftKerjas()->sync([$user->shift_kerja_id]);
        }

        \Log::info('audit:user.edit.success', [
            'actor' => auth()->id(),
            'record' => $user->id,
            'changes' => $user->getChanges(),
        ]);

        Notification::make()
            ->title('Data pegawai berhasil diperbarui')
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'employee') {
                $restricted = [
                    'departemen_id',
                    'role',
                    'jabatan_id',
                    'shift_kerja_id',
                    'shift_kerjas',
                    'company_location_id',
                    'company_locations',
                ];
                foreach ($restricted as $key) {
                    if (array_key_exists($key, $data)) {
                        $old = $record->{$key} ?? null;
                        $new = $data[$key];
                        if ($new !== $old) {
                            \Log::info('audit:user.edit.blocked', ['actor' => auth()->id(), 'field' => $key]);
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                $key => 'Anda tidak diizinkan mengubah field ini',
                            ]);
                        }
                        unset($data[$key]);
                    }
                }
            }

            if ($role === 'kepala_sub_bagian') {
                $restricted = [
                    'shift_kerja_id',
                    'shift_kerjas',
                    'company_location_id',
                    'company_locations',
                ];
                foreach ($restricted as $key) {
                    if (array_key_exists($key, $data)) {
                        $old = $record->{$key} ?? null;
                        $new = $data[$key];
                        if ($new !== $old) {
                            \Log::info('audit:user.edit.blocked', ['actor' => auth()->id(), 'field' => $key]);
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                $key => 'Anda tidak diizinkan mengubah field ini',
                            ]);
                        }
                        unset($data[$key]);
                    }
                }
            }
        }

        if (array_key_exists('password', $data)) {
            $pwd = (string) ($data['password'] ?? '');
            if ($pwd === '') {
                unset($data['password']);
            }
        }

        return $data;
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
