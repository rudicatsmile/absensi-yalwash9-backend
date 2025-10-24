<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $meeting = $this->record;
        $user = $meeting->employee;

        if ($meeting->wasChanged('status')) {
            $statusText = match ($meeting->status) {
                'approved' => 'disetujui',
                'rejected' => 'ditolak',
                default => 'pending'
            };

            // Send notification to user
            Notification::make()
                ->title('Status Meeting Diperbarui')
                ->body("Meeting Anda pada {$meeting->date} telah {$statusText}")
                ->success()
                ->send();

            // Send Firebase notification if user has FCM token
            if ($user && $user->fcm_token) {
                try {
                    $messaging = app('firebase.messaging');
                    
                    $notification = FirebaseNotification::create(
                        'Status Meeting Diperbarui',
                        "Meeting Anda pada {$meeting->date} telah {$statusText}"
                    );

                    $message = CloudMessage::withTarget('token', $user->fcm_token)
                        ->withNotification($notification)
                        ->withData([
                            'type' => 'meeting_status_update',
                            'meeting_id' => (string) $meeting->id,
                            'status' => $meeting->status,
                        ]);

                    $messaging->send($message);
                } catch (\Exception $e) {
                    // Log error but don't fail the operation
                    logger()->error('Failed to send Firebase notification: ' . $e->getMessage());
                }
            }
        }
    }
}