<?php

namespace App\Filament\Resources\JamKerjas\Schemas;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\JamKerja;
use Carbon\Carbon;

class JamKerjaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shift Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Shift Name')
                            ->required()
                            ->placeholder('e.g., Morning Shift, Night Shift')
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                TimePicker::make('start_time')
                                    ->label('Start Time')
                                    ->required()
                                    ->seconds(false)
                                    ->rule(function (\Filament\Schemas\Components\Utilities\Get $get, $record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $startTime = $value;
                                            $endTime = $get('end_time');
                                            $isCrossDay = $get('is_cross_day');

                                            if (!$startTime || !$endTime)
                                                return;

                                            $otherShifts = JamKerja::query()
                                                ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                ->get();

                                            $newStart = Carbon::parse($startTime);
                                            $newEnd = Carbon::parse($endTime);
                                            if ($isCrossDay)
                                                $newEnd->addDay();

                                            foreach ($otherShifts as $shift) {
                                                $existingStart = Carbon::parse($shift->start_time);
                                                $existingEnd = Carbon::parse($shift->end_time);
                                                if ($shift->is_cross_day)
                                                    $existingEnd->addDay();

                                                $checkOverlap = function ($s1, $e1, $s2, $e2) {
                                                    return $s1 < $e2 && $e1 > $s2;
                                                };

                                                $ns1 = $newStart->copy()->setDate(2000, 1, 1);
                                                $ne1 = $newEnd->copy()->setDate(2000, 1, 1);
                                                if ($isCrossDay)
                                                    $ne1->addDay();

                                                $es1 = $existingStart->copy()->setDate(2000, 1, 1);
                                                $ee1 = $existingEnd->copy()->setDate(2000, 1, 1);
                                                if ($shift->is_cross_day)
                                                    $ee1->addDay();

                                                if ($checkOverlap($ns1, $ne1, $es1, $ee1)) {
                                                    $fail("Shift time overlaps with '{$shift->name}' ({$shift->start_time} - {$shift->end_time}).");
                                                    return;
                                                }

                                                // Check previous day overlap
                                                $es_prev = $es1->copy()->subDay();
                                                $ee_prev = $ee1->copy()->subDay();
                                                if ($checkOverlap($ns1, $ne1, $es_prev, $ee_prev)) {
                                                    $fail("Shift time overlaps with '{$shift->name}' (Previous Day).");
                                                    return;
                                                }

                                                // Check next day overlap
                                                $es_next = $es1->copy()->addDay();
                                                $ee_next = $ee1->copy()->addDay();
                                                if ($checkOverlap($ns1, $ne1, $es_next, $ee_next)) {
                                                    $fail("Shift time overlaps with '{$shift->name}' (Next Day).");
                                                    return;
                                                }
                                            }
                                        };
                                    }),

                                TimePicker::make('end_time')
                                    ->label('End Time')
                                    ->required()
                                    ->seconds(false),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Optional description about this shift')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Shift Settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_cross_day')
                                    ->label('Cross Midnight')
                                    ->helperText('Check if this shift crosses midnight (e.g., 23:00 - 07:00)')
                                    ->default(false)
                                    ->reactive(),

                                TextInput::make('grace_period_minutes')
                                    ->label('Grace Period (minutes)')
                                    ->helperText('Late tolerance in minutes')
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Only active shifts can be assigned')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
