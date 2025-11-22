<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check() && in_array(auth()->user()->role, ['manager','kepala_sub_bagian'], true)) {
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
