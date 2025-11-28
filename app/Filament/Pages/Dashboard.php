<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\LatestAttendanceWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Filament\Widgets\PendingOvertimeWidget;
use BackedEnum;
use Carbon\Carbon; // Tambahkan import ini
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $title = 'Dashboard Absensi';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    // Tombol Filter di header (pojok kanan atas)
    protected function getHeaderActions(): array
    {
        $activeDate = $this->getActiveFilterDate(); // Carbon atau null

        return [
            Action::make('filter')
                ->label($activeDate ? $activeDate->translatedFormat('d M Y') : 'Pilih Tanggal') // Perbaiki: cek null, gunakan translatedFormat
                ->icon('heroicon-m-funnel')
                ->color($activeDate ? 'primary' : 'gray')
                ->form([
                    DatePicker::make('date')
                        ->label('Tanggal Kehadiran')
                        ->maxDate(today())
                        ->default(today()),
                ])
                ->action(function (array $data) {
                    $this->applyFilter($data['date'] ?? null);
                })
                ->closeModalByClickingAway(false),

            // Tombol reset filter
            Action::make('reset')
                ->label('Reset')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->visible(fn() => $this->getActiveFilterDate() !== null)
                ->action(fn() => $this->applyFilter(null)),
        ];
    }

    public function getWidgets(): array
    {
        return [
            AttendanceChartWidget::class,
                // LatestAttendanceWidget::class,
            PendingApprovalsWidget::class,
            PendingOvertimeWidget::class,
            AccountWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    // Helper untuk ambil tanggal filter aktif (perbaiki: parse ke Carbon)
    public function getActiveFilterDate(): ?Carbon
    {
        $dateString = session('dashboard_filter_date');
        return $dateString ? Carbon::parse($dateString) : null; // Parse string ke Carbon, fallback null
    }

    // Simpan filter ke session (biar tetap saat pindah halaman)
    protected function applyFilter(?string $date): void
    {
        if ($date) {
            session(['dashboard_filter_date' => $date]);
        } else {
            session()->forget('dashboard_filter_date');
        }

        // Refresh semua widget
        $this->dispatch('refresh-widgets');
    }
}
