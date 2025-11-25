<?php

namespace App\Filament\Resources\ReligiousStudies\Tables;

use App\Models\ReligiousStudyEvent;
use App\Services\FcmService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\UserPushToken;
use App\Models\NotificationLog;
use Filament\Notifications\Notification;

class ReligiousStudyEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                TextColumn::make('event_at')->label('Waktu')->dateTime()->sortable(),
                TextColumn::make('notify_at')->label('Kirim Pada')->dateTime()->sortable(),
                TextColumn::make('location')->label('Lokasi'),
                BadgeColumn::make('cancelled')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state ? 'Dibatalkan' : 'Aktif')
                    ->colors([
                        'success' => fn($state) => !$state,
                        'danger' => fn($state) => $state,
                    ]),
                BadgeColumn::make('notified')
                    ->label('Sudah Dikirim')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Belum')
                    ->colors([
                        'success' => fn($state) => $state,
                        'warning' => fn($state) => !$state,
                    ]),
            ])
            ->filters([
                SelectFilter::make('cancelled')->options([
                    0 => 'Aktif',
                    1 => 'Dibatalkan',
                ])->label('Status'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('send_now')
                    ->label('Kirim Sekarang')
                    ->requiresConfirmation()
                    ->action(function (ReligiousStudyEvent $record) {
                        if ($record->cancelled) {
                            return;
                        }
                        $title = $record->title ?: 'Event Notifikasi';
                        $time = \Carbon\Carbon::parse($record->event_at)->format('d-m-Y H:i');
                        $body = 'Waktu: ' . $time . "\nLokasi: " . ($record->location ?? '-') . "\nTema: " . ($record->theme ?? '-') . "\nPemateri: " . ($record->speaker ?? '-');

                        $imageUrl = '';
                        $path = (string) ($record->image_path ?? '');
                        if ($path !== '' && Storage::disk('public')->exists($path)) {
                            $imageUrl = Storage::disk('public')->url($path);
                        } else {
                            Notification::make()
                                ->title('Gambar tidak ditemukan')
                                ->body('Notifikasi dikirim tanpa gambar')
                                ->warning()
                                ->send();
                        }

                        $data = [
                            'type' => 'religious_event_notify',
                            'event_id' => (string) $record->id,
                            'time' => $time,
                            'location' => (string) ($record->location ?? ''),
                            'theme' => (string) ($record->theme ?? ''),
                            'speaker' => (string) ($record->speaker ?? ''),
                            'image_path' => $imageUrl,
                        ];
                        $tokens = UserPushToken::query()->pluck('token')->all();
                        $ok = app(FcmService::class)->sendToTokens($tokens, $title, $body, $data, 1);
                        NotificationLog::create([
                            'event_id' => $record->id,
                            'type' => 'religious_event_notify',
                            'title' => $title,
                            'body' => $body,
                            'success_count' => $ok ? count($tokens) : 0,
                            'failure_count' => $ok ? 0 : count($tokens),
                        ]);
                        $record->notified = true;
                        $record->save();
                    }),
                Action::make('cancel')
                    ->label('Batalkan')
                    ->requiresConfirmation()
                    ->action(function (ReligiousStudyEvent $record) {
                        $record->cancelled = true;
                        $record->save();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
