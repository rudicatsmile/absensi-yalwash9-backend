<?php

namespace App\Filament\Resources\Meetings\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meetingType.name')
                    ->label('Jenis Meeting')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Agenda/Alasan')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('document')
                    ->label('Dokumen PDF')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return 'Tidak Ada';
                        }

                        $url = Storage::url($state);

                        return "<a href='{$url}' target='_blank' class='text-blue-600 hover:text-blue-800 underline'>
                                    Lihat Dokumen
                                </a>";
                    })
                    ->html(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => $state,
                        };
                    }),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (! $state || strlen($state) <= 30) {
                            return null;
                        }

                        return $state;
                    })
                    ->placeholder('Tidak ada catatan'),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('Belum disetujui'),

                TextColumn::make('approved_at')
                    ->label('Tanggal Persetujuan')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Belum disetujui'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                SelectFilter::make('meeting_type_id')
                    ->label('Jenis Meeting')
                    ->relationship('meetingType', 'name'),

                SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->options(\App\Models\User::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (\Livewire\Component $livewire) {
                        return $livewire->exportToPdf();
                    }),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}