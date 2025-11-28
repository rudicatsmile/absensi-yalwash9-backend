<?php

namespace App\Filament\Pages;

use App\Models\Departemen;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\DatePicker;
use UnitEnum;
use BackedEnum;
class LaporanPerDepartemen extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Per Departemen';
    protected static ?string $title = 'Laporan Kehadiran Per Departemen';
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.laporan-per-departemen';

    public $departmentStats = [];

    public $date;
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortBy = $field;
        $this->departmentStats = $this->getDepartmentAttendanceStats();
    }

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->toDateString(),
        ]);

        $this->date = $this->form->getState()['date'];
        $this->departmentStats = $this->getDepartmentAttendanceStats();
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->reactive()
                ->afterStateUpdated(function () {
                    $this->date = $this->form->getState()['date'];
                    $this->departmentStats = $this->getDepartmentAttendanceStats();
                }),
        ];
    }

    private function getDepartmentAttendanceStats(): array
    {
        $departemens = Departemen::select('id', 'name')->get();
        $stats = [];

        $totalPerDepartemen = User::select('departemen_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('departemen_id')
            ->groupBy('departemen_id')
            ->pluck('total', 'departemen_id');

        $hadirPerDepartemen = Attendance::select('departemen_id', DB::raw('COUNT(DISTINCT user_id) as hadir'))
            ->whereDate('date', $this->date)
            ->whereNotNull('time_in')
            ->groupBy('departemen_id')
            ->pluck('hadir', 'departemen_id');

        foreach ($departemens as $dept) {
            $totalEmployees = (int) ($totalPerDepartemen[$dept->id] ?? 0);
            $todayAttendance = (int) ($hadirPerDepartemen[$dept->id] ?? 0);
            $percentage = $totalEmployees > 0 ? ($todayAttendance / $totalEmployees) * 100 : 0;

            $stats[] = [
                'name' => $dept->name,
                'attendance' => $todayAttendance . ' / ' . $totalEmployees,
                'percentage' => sprintf('%.2f%%', $percentage),
            ];
        }

        $statsCollection = collect($stats);

        $sortedStats = $statsCollection->sortBy($this->sortBy, SORT_REGULAR, $this->sortDirection === 'desc');

        return $sortedStats->values()->all();
    }
}
