<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn() => 'data:image/svg+xml;base64,' . base64_encode('
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" style="background-color: #F3F4F6;">
                            <g transform="translate(25, 25)">
                                <path fill="#9CA3AF" fill-rule="evenodd" d="M-5 -10a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM-8.249 4.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 010 6.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
                            </g>
                        </svg>
                    '))
                    ->size(50),
                TextColumn::make('name')
                    ->label('Nama / Email')
                    ->getStateUsing(function ($record) {
                        $name = e($record->name ?? '');
                        $email = e($record->email ?? '');
                        return "<div>{$name}<div class=\"text-xs italic text-slate-500\">{$email}</div></div>";
                    })
                    ->html()
                    ->searchable(['name', 'email'])
                    ->sortable(),

                // TextColumn::make('role')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'admin' => 'danger',
                //         'manager' => 'warning',
                //         'employee' => 'success',
                //         default => 'gray',
                //     })
                //     ->searchable(),
                // TextColumn::make('jabatan.name')
                //     ->label('Jabatan')
                //     ->badge()
                //     ->color('info')
                //     ->searchable()
                //     ->sortable()
                //     ->placeholder('Belum diset')
                //     ->icon('heroicon-o-briefcase'),
                TextColumn::make('departemen.name')
                    ->label('Unit Kerja')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diset')
                    ->icon('heroicon-o-building-library'),
                // TextColumn::make('shiftKerja.name')
                //     ->label('Shift')
                //     ->badge()
                //     ->color('warning')
                //     ->searchable()
                //     ->sortable()
                //     ->placeholder('Belum diset')
                //     ->icon('heroicon-o-clock'),


                TextColumn::make('shift')
                    ->label('Shift Kerja')
                    ->wrap()
                    ->color('warning')
                    ->getStateUsing(function ($record) {
                        // Ambil nama shift kerja dari relasi pivot shiftKerjas (join: shift_kerja_user -> shift_kerjas)
                        $names = $record->shiftKerjas?->pluck('name')->filter()->all() ?? [];
                        return count($names) ? implode(', ', $names) : null;
                    })
                    ->placeholder('Belum diset')
                    ->icon('heroicon-o-clock'),


                TextColumn::make('location')
                    ->label('Lokasi')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        // Ambil nama lokasi dari relasi pivot companyLocations (join: company_location_user -> company_locations)
                        $names = $record->companyLocations?->pluck('name')->filter()->all() ?? [];
                        return count($names) ? implode(', ', $names) : null;
                    })
                    ->placeholder('Belum diset')
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'kepala_lembaga' => 'Pimpinan Yayasan',
                        'manager' => 'Kepala Bagian / Kepala Sekolah',
                        'kepala_sub_bagian' => 'Kepala Sub Bagian',
                        'employee' => 'Employee',
                    ]),
                SelectFilter::make('departemen_id')
                    ->label('Departemen')
                    ->options(function () {
                        $base = \App\Models\Departemen::query()->orderBy('name');
                        if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
                            $base->whereKey(auth()->user()->departemen_id);
                        }
                        return $base->pluck('name', 'id')->toArray();
                    })
                    ->preload()
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        $id = $data['value'] ?? null;
                        if ($id && \App\Models\Departemen::whereKey($id)->exists()) {
                            $query->where('departemen_id', $id);
                        }
                    }),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (\Livewire\Component $livewire) {
                        return $livewire->exportUsersExcel();
                    }),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (\Livewire\Component $livewire) {
                        return $livewire->exportUsersPdf();
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(function ($record) {
                        if (!auth()->check())
                            return true;
                        $role = auth()->user()->role;
                        if ($role === 'employee') {
                            return auth()->id() === ($record->id ?? null);
                        }
                        if (in_array($role, ['manager', 'kepala_sub_bagian'], true)) {
                            return (auth()->user()->departemen_id ?? null) === ($record->departemen_id ?? null);
                        }
                        return true;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function () {
                            if (!auth()->check())
                                return false;
                            return !in_array(auth()->user()->role, ['employee'], true);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
