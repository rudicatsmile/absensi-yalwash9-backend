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

                TextColumn::make('event_at')
                    ->label('Waktu & Lokasi')
                    ->sortable()
                    ->html()
                    ->formatStateUsing(fn($record) => $record->event_at->translatedFormat('l, d F Y') . '<br>' . $record->location),
                // TextColumn::make('departemen_ids')
                //     ->label('Departemen'),
                /*
                TextColumn::make('departemen_ids')
                    ->label('Departemen')
                    ->formatStateUsing(function (ReligiousStudyEvent $record) {
                        $names = $record->getAllDepartemenNames();
                        if ($names->isEmpty()) {
                            return '-';
                        }
                        $seen = [];
                        $unique = [];
                        foreach ($names as $name) {
                            $n = trim((string) $name);
                            if ($n === '') {
                                continue;
                            }
                            $key = \Illuminate\Support\Str::of($n)->lower()->squish()->value();
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $seen[$key] = true;
                            $unique[] = $n;
                        }
                        if (empty($unique)) {
                            return '-';
                        }
                        return implode(', ', $unique);
                    }),
                TextColumn::make('jabatan_ids')
                    ->label('Jabatan')
                    ->formatStateUsing(function (ReligiousStudyEvent $record) {
                        $names = $record->getAllJabatanNames();
                        if ($names->isEmpty()) {
                            return '-';
                        }
                        $display = $names->implode(', ');
                        return $names->count() > 1 ? $display . ' (' . $names->count() . ')' : $display;
                    }),
                    */
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

                BadgeColumn::make('isoverlay')
                    ->label('Overlay')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
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
                SelectFilter::make('departemen_id')
                    ->label('Departemen')
                    ->options(\App\Models\Departemen::query()->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('jabatan_id')
                    ->label('Jabatan')
                    ->options(\App\Models\Jabatan::query()->orderBy('name')->pluck('name', 'id')->toArray()),
            ])
            ->query(\App\Models\ReligiousStudyEvent::query()->with(['departemen', 'jabatan']))
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
                            'departemen_id' => (string) ($record->departemen_id ?? ''),
                            'jabatan_id' => (string) ($record->jabatan_id ?? ''),
                        ];
                        $tokens = UserPushToken::query()->pluck('token')->all();
                        // Kirim ke banyak departemen jika dipilih (departemen_ids)
                        $deptIds = is_array($record->departemen_ids) ? $record->departemen_ids : [];
                        foreach ($deptIds as $deptId) {
                            if ($deptId) {
                                $ok = app(FcmService::class)->sendToDepartmentUsers((int) $deptId, $title, $body, $data);
                            }
                        }
                        // Kirim ke banyak jabatan jika dipilih (jabatan_ids)
                        $jabIds = is_array($record->jabatan_ids) ? $record->jabatan_ids : [];
                        foreach ($jabIds as $jabId) {
                            if ($jabId) {
                               $ok =  app(FcmService::class)->sendToJabatanUsers((int) $jabId, $title, $body, $data);
                            }
                        }

                        //Jika tidak ada pilihan khusus, kirim ke semua pengguna
                        if (empty($deptIds) && empty($jabIds) && !$record->departemen_id && !$record->jabatan_id) {
                            $ok = app(FcmService::class)->sendToTokens($tokens, $title, $body, $data, 1);
                        }

                        //$ok = app(FcmService::class)->sendToTokens($tokens, title: $title, $body, $data, 1);
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
