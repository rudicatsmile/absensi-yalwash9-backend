<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
// Notification import is appended at end of file to avoid duplicate alias

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager'], true)) {
            \Log::info('audit:user.create.forbidden', ['actor' => auth()->id()]);
            if (request()->expectsJson()) {
                response(['message' => 'Akses ditolak: Anda tidak berhak menambah pegawai'], 403)->send();
                exit;
            }
            abort(403, 'Akses ditolak: Anda tidak berhak menambah pegawai');
        }
        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
            $userDept = auth()->user()->departemen_id;
            if (is_null($userDept)) {
                \Log::info('audit:user.create.blocked', ['actor' => auth()->id(), 'reason' => 'actor departemen null']);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'departemen_id' => 'Profil Anda belum memiliki departemen. Hubungi admin untuk mengatur departemen Anda.',
                ]);
            }
            if (($data['departemen_id'] ?? null) !== $userDept) {
                \Log::info('audit:user.create.blocked', ['actor' => auth()->id(), 'target_dept' => $data['departemen_id'] ?? null, 'actor_dept' => $userDept]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'departemen_id' => 'Departemen untuk user baru harus sama dengan departemen Anda',
                ]);
            }
            $allowedRoles = auth()->user()->role === 'manager' ? ['kepala_sub_bagian', 'employee'] : ['employee'];
            if (!in_array(($data['role'] ?? 'employee'), $allowedRoles, true)) {
                \Log::info('audit:user.create.blocked', ['actor' => auth()->id(), 'reason' => 'role not allowed', 'role' => $data['role'] ?? null]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'role' => 'Tipe User tidak diizinkan untuk peran Anda',
                ]);
            }

            if (!empty($data['jabatan_id'])) {
                $jabatan = \App\Models\Jabatan::find($data['jabatan_id']);
                $allowedJabatan = ['Kasubag', 'Pegawai'];
                if (!$jabatan || !in_array($jabatan->name, $allowedJabatan, true)) {
                    \Log::info('audit:user.create.blocked', ['actor' => auth()->id(), 'reason' => 'jabatan not allowed', 'jabatan_id' => $data['jabatan_id'] ?? null]);
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'jabatan_id' => 'Jabatan tidak diizinkan untuk peran Anda',
                    ]);
                }
            }
        }
        return $data;
    }

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $currentYear = now()->year;

        // Get all active leave types
        $leaveTypes = LeaveType::all();

        // Create leave balance for each leave type
        foreach ($leaveTypes as $leaveType) {
            LeaveBalance::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => $currentYear,
                'quota_days' => $leaveType->quota_days,
                'used_days' => 0,
                'remaining_days' => $leaveType->quota_days,
                'carry_over_days' => 0,
                'last_updated' => now(),
            ]);
        }

        // Sinkronisasi pivot: departemen_user, jabatan_user, shift_kerja_user
        if (!empty($user->departemen_id)) {
            $user->departemens()->sync([$user->departemen_id]);
        }
        if (!empty($user->jabatan_id)) {
            $user->jabatans()->sync([$user->jabatan_id]);
        }
        // if (! empty($user->shift_kerja_id)) {
        //     $user->shiftKerjas()->sync([$user->shift_kerja_id]);
        // }
    }

    public function getFormActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Batal')
                ->color('secondary')
                ->outlined()
                ->url($this->getResource()::getUrl('index')),
            Action::make('save')
                ->label('Simpan')
                ->submit('save')
                ->color('primary')
                ->action(function () {
                    try {
                        $this->save();
                        \Log::info('audit:user.create.success', ['actor' => auth()->id(), 'record' => $this->record->id ?? null]);
                        Notification::make()
                            ->title('User berhasil dibuat')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Throwable $e) {
                        \Log::error('audit:user.create.exception', ['message' => $e->getMessage()]);
                        Notification::make()
                            ->title('Gagal membuat user')
                            ->body('Periksa kembali input Anda. Jika masalah berlanjut, hubungi admin.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
use Filament\Notifications\Notification;
