<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\DepartmentStatsWidget;
use App\Filament\Widgets\LatestAttendanceWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Filament\Widgets\PendingOvertimeWidget;
use App\Filament\Widgets\PendingPermitsWidget;
use BackedEnum;
use Carbon\Carbon; // Tambahkan import ini
use Filament\Forms\Components\Select;
use App\Models\ShiftKerja;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Log; // Tambahkan untuk logging

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
        // Ambil nilai dari sesi dengan validasi
        $activeDate = $this->getActiveFilterDate();
        $activeShift = session('dashboard_filter_shift');

        return [
            Action::make('filter')
                ->label($activeDate ? $activeDate->translatedFormat('d M Y') : 'Pilih Tanggal')
                ->icon('heroicon-m-funnel')
                ->color($activeDate ? 'primary' : 'gray')
                ->form([
                    DatePicker::make('date')
                        ->label('Tanggal Kehadiran')
                        ->maxDate(today())
                        ->default($activeDate ?? today()), // Isi dari sesi atau hari ini
                    Select::make('shift_id')
                        ->label('Pilih Shift Kerja')
                        ->options(ShiftKerja::all()->pluck('name', 'id'))
                        ->placeholder('Semua Shift')
                        ->default($activeShift), // Isi dari sesi
                ])
                ->action(function (array $data) {
                    $this->applyFilter($data['date'] ?? null, $data['shift_id'] ?? null);
                })
                ->closeModalByClickingAway(false),

            // Tombol reset filter
            Action::make('reset')
                ->label('Reset')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->visible(fn() => $this->getActiveFilterDate() !== null || session('dashboard_filter_shift') !== null)
                ->action(fn() => $this->applyFilter(null, null)),
        ];
    }

    public function getWidgets(): array
    {
        return [
            AttendanceChartWidget::class,
                // LatestAttendanceWidget::class,
            PendingPermitsWidget::class,
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
            DepartmentStatsWidget::class,
        ];
    }

    // Helper untuk ambil tanggal filter aktif (perbaiki: parse ke Carbon)
    public function getActiveFilterDate(): ?Carbon
    {
        $dateString = session('dashboard_filter_date');

        if (!$dateString) {
            return null;
        }

        $date = Carbon::parse($dateString);

        // Validasi: Jika tanggal di sesi lebih besar dari hari ini, hapus sesi dan kembalikan null.
        if ($date->isAfter(today())) {
            Log::warning('Invalid date found in session, resetting.', ['date' => $dateString, 'user_id' => auth()->id()]);
            session()->forget('dashboard_filter_date');
            return null;
        }

        Log::info('Dashboard filter date loaded from session', ['date' => $dateString, 'user_id' => auth()->id()]);
        return $date;
    }

    // Simpan filter ke session (biar tetap saat pindah halaman)
    protected function applyFilter(?string $date, ?string $shiftId): void
    {
        if ($date) {
            session(['dashboard_filter_date' => $date]);
            Log::info('Dashboard filter date set', ['date' => $date, 'user_id' => auth()->id()]);
        } else {
            session()->forget('dashboard_filter_date');
            Log::info('Dashboard filter date reset', ['user_id' => auth()->id()]);
        }

        if ($shiftId) {
            session(['dashboard_filter_shift' => $shiftId]);
            Log::info('Dashboard filter shift set', ['shift_id' => $shiftId, 'user_id' => auth()->id()]);
        } else {
            session()->forget('dashboard_filter_shift');
            Log::info('Dashboard filter shift reset', ['user_id' => auth()->id()]);
        }

        // Refresh semua widget
        $this->dispatch('refresh-widgets');
    }
}
