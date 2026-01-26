<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '15s';

    protected static ?array $grid = [
        'default' => 2,
        'md' => 5,
        'lg' => 5, // 5 card dalam 1 baris di layar besar
    ];

    protected function getSelectedDate(): Carbon
    {
        $dateString = session('dashboard_filter_date') ?? today()->format('Y-m-d');
        return Carbon::parse($dateString);
    }

    protected function getSelectedShift(): ?string
    {
        return session('dashboard_filter_shift');
    }

    protected function getStats(): array
    {
        $selectedDate = $this->getSelectedDate();
        $selectedShift = $this->getSelectedShift();

        // Cek apakah user memiliki role yang perlu filter berdasarkan departemen
        $currentUser = auth()->user();
        $isDepartmentFiltered = in_array($currentUser->role, ['manager', 'kepala_sub_bagian', 'employee']);
        $userDepartmentId = $currentUser->departemen_id;

        // Helper untuk filter query
        $applyFilters = function ($query) use ($isDepartmentFiltered, $userDepartmentId, $selectedShift) {
            if ($isDepartmentFiltered) {
                // Asumsi relasi user ada departemen_id atau lewat employee
                // Jika modelnya User (untuk total pegawai):
                if ($query->getModel() instanceof User) {
                    $query->where('departemen_id', $userDepartmentId);
                }
                // Jika model Attendance/Leave/Permit yang punya relasi user/employee:
                else {
                    // Cek nama relasi, biasanya 'user' atau 'employee'
                    // Di Attendance: user()
                    // Di Leave/Permit: employee() (User model)
                    $relationName = method_exists($query->getModel(), 'employee') ? 'employee' : 'user';

                    $query->whereHas($relationName, function ($q) use ($userDepartmentId) {
                        $q->where('departemen_id', $userDepartmentId);
                    });
                }
            }

            if ($selectedShift) {
                // Jika model punya shift_id langsung (Attendance)
                // Atau perlu join user->shift_id (tapi shift user bisa beda dengan shift attendance?)
                // Di kode lama: Attendance pakai shift_id kolom, Leave/Permit join employee->shift_id

                if ($query->getModel() instanceof Attendance) {
                    $query->where('shift_id', $selectedShift);
                } else {
                    $relationName = method_exists($query->getModel(), 'employee') ? 'employee' : 'user';
                    $query->whereHas($relationName, function ($q) use ($selectedShift) {
                        $q->where('shift_id', $selectedShift);
                    });
                }
            }
        };

        // 1. Total Pegawai
        $pegawaiQuery = User::query();
        if ($isDepartmentFiltered) {
            $pegawaiQuery->where('departemen_id', $userDepartmentId);
        }
        $totalPegawaiCount = $pegawaiQuery->count();

        // 2. Total Hadir
        $hadirQuery = Attendance::whereDate('date', $selectedDate);
        $applyFilters($hadirQuery);
        $totalHadir = $hadirQuery->count();

        // 3. Total Tidak Hadir
        // Logika sederhana: Total Pegawai - Total Hadir
        // Catatan: Ini asumsi sederhana, bisa jadi tidak akurat jika ada yang libur/off tapi dihitung 'tidak hadir'
        // Tapi mengikuti logika kode sebelumnya: $totalPegawaiCount - $totalHadir
        // Pastikan tidak negatif

        // 4. Total Izin
        $izinQuery = \App\Models\Permit::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->where('status', 'approved')
            ->where('permit_type_id', '<>', 4);
        $applyFilters($izinQuery);
        $totalIzin = $izinQuery->count();

        // 5. Total Cuti
        $cutiQuery = \App\Models\Leave::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->where('status', 'approved');
        $applyFilters($cutiQuery);
        $totalCuti = $cutiQuery->count();


        $totalTidakHadir = $totalPegawaiCount - $totalHadir - $totalIzin - $totalCuti;
        // Pastikan tidak negatif
        $totalTidakHadir = max(0, $totalTidakHadir);

        $stats = [
            Stat::make('Total Pegawai', $totalPegawaiCount)
                ->description($isDepartmentFiltered ? 'Pegawai di lembaga Anda' : 'Seluruh karyawan')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'border-b-4 border-blue-500 shadow-lg rounded-xl hover:scale-105 transition-transform duration-300 bg-white dark:bg-gray-800',
                ]),

            Stat::make('Total Hadir', $totalHadir)
                ->description('Pegawai yang hadir')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([15, 12, 18, 14, 20, 15, 19])
                ->extraAttributes([
                    'class' => 'border-b-4 border-green-500 shadow-lg rounded-xl hover:scale-105 transition-transform duration-300 bg-white dark:bg-gray-800',
                ]),

            Stat::make('Total Tidak Hadir', $totalTidakHadir)
                ->description('Pegawai yang tidak hadir')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([3, 5, 2, 4, 1, 6, 3])
                ->extraAttributes([
                    'class' => 'border-b-4 border-red-500 shadow-lg rounded-xl hover:scale-105 transition-transform duration-300 bg-white dark:bg-gray-800',
                ]),

            Stat::make('Total Izin', $totalIzin)
                ->description('Pegawai yang izin')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->chart([1, 0, 2, 1, 3, 1, 2])
                ->extraAttributes([
                    'class' => 'border-b-4 border-amber-500 shadow-lg rounded-xl hover:scale-105 transition-transform duration-300 bg-white dark:bg-gray-800',
                ]),

            Stat::make('Total Cuti', $totalCuti)
                ->description('Pegawai yang sedang cuti')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning')
                ->chart([0, 1, 0, 2, 1, 0, 1])
                ->extraAttributes([
                    'class' => 'border-b-4 border-purple-500 shadow-lg rounded-xl hover:scale-105 transition-transform duration-300 bg-white dark:bg-gray-800',
                ]),
        ];

        return $stats;
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}
