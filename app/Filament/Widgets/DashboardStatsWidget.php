<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters; // Tetap pakai ini agar auto-refresh saat filter berubah

    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '15s';

    protected static ?array $grid = [
        'default' => 2,
        'md' => 3,
        'lg' => 6,
    ];

    protected function getSelectedDate(): Carbon
    {
        $dateString = session('dashboard_filter_date') ?? today()->format('Y-m-d'); // Fallback ke hari ini sebagai string
        return Carbon::parse($dateString); // Selalu parse ke Carbon
    }

    protected function getSelectedShift(): ?string
    {
        return session('dashboard_filter_shift');
    }

    protected function getStats(): array
    {
        $selectedDate = $this->getSelectedDate();
        $selectedShift = $this->getSelectedShift();

        //  ...Departemen::withCount('users')->get()->map(function ($departemen) {
        //         $attendanceCount = Attendance::where('departemen_id', $departemen->id)->whereDate('created_at', today())->count();
        //         $totalUsers = $departemen->users_count;
        //         $percentage = ($totalUsers > 0) ? round(($attendanceCount / $totalUsers) * 100) : 0;

        //         return Stat::make($departemen->name, $attendanceCount)
        //             ->description($percentage . '% karyawan hadir')
        //             ->descriptionIcon('heroicon-m-check-circle')
        //             ->color('primary');
        //     })->toArray(),



        // $stats = Departemen::withCount('users')->get()->map(function (Departemen $dept) use ($selectedDate) {
        //     $hadir = Attendance::where('departemen_id', $dept->id)
        //         ->whereDate('date', $selectedDate)
        //         ->count();

        $query = "
            SELECT
                d.id,
                d.name,
                COUNT(u.id) AS total_users,
                (
                    SELECT COUNT(*)
                    FROM attendances a
                    JOIN users u2 ON a.user_id = u2.id
                    WHERE u2.departemen_id = d.id
                    AND DATE(a.date) = ? " . ($selectedShift ? " AND a.shift_id = ?" : "") . "
                ) AS attendance_count
            FROM
                departemens d
            LEFT JOIN
                users u ON d.id = u.departemen_id
            GROUP BY
                d.id, d.name
            ORDER BY
                d.id
        ";

        $bindings = [$selectedDate->toDateString()];
        if ($selectedShift) {
            $bindings[] = $selectedShift;
        }

        $departmentStats = \Illuminate\Support\Facades\DB::select($query, $bindings);

        // Cek apakah user memiliki role yang perlu filter berdasarkan departemen
        $currentUser = auth()->user();
        $isDepartmentFiltered = in_array($currentUser->role, ['manager', 'kepala_sub_bagian', 'employee']);
        $userDepartmentId = $currentUser->departemen_id;

        // Total Pegawai - filter berdasarkan departemen untuk role tertentu
        $totalPegawaiCount = $isDepartmentFiltered
            ? User::where('departemen_id', $userDepartmentId)->count()
            : User::count();

        $stats[] = Stat::make('Total Pegawai', $totalPegawaiCount)
            ->description($isDepartmentFiltered ? 'Pegawai di lembaga Anda' : 'Seluruh karyawan')
            ->descriptionIcon('heroicon-m-users')
            ->color('primary');

        // Total Hadir - filter berdasarkan departemen untuk role tertentu
        $totalHadir = $isDepartmentFiltered
            ? Attendance::where('departemen_id', $userDepartmentId)
                ->whereDate('date', $selectedDate)
                ->when($selectedShift, fn($q) => $q->where('shift_id', $selectedShift))
                ->count()
            : array_sum(array_column($departmentStats, 'attendance_count'));

        $stats[] = Stat::make('Total Hadir', $totalHadir)
            ->description('Pegawai yang hadir')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('primary');

        // Total Tidak Hadir - filter berdasarkan departemen untuk role tertentu
        $totalTidakHadir = $totalPegawaiCount - $totalHadir;
        $stats[] = Stat::make('Total Tidak Hadir', $totalTidakHadir)
            ->description('Pegawai yang tidak hadir')
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');

        // Total Izin - filter berdasarkan departemen dan shift untuk role tertentu
        $totalIzinQuery = \App\Models\Permit::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate);

        // Filter berdasarkan departemen untuk role tertentu
        if ($isDepartmentFiltered) {
            $totalIzinQuery->whereHas('employee', function ($query) use ($userDepartmentId) {
                $query->where('departemen_id', $userDepartmentId);
            });
        }

        // Filter berdasarkan shift jika dipilih
        $totalIzinQuery->when($selectedShift, function ($query) use ($selectedShift) {
            $query->whereHas('employee', function ($subQuery) use ($selectedShift) {
                $subQuery->where('shift_id', $selectedShift);
            });
        });

        $totalIzin = $totalIzinQuery->count();
        $stats[] = Stat::make('Total Izin', $totalIzin)
            ->description('Pegawai yang izin')
            ->descriptionIcon('heroicon-m-clipboard-document-check')
            ->color('info');

        // Total Cuti - filter berdasarkan departemen dan shift untuk role tertentu
        $totalCutiQuery = \App\Models\Leave::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->where('status', 'approved');

        // Filter berdasarkan departemen untuk role tertentu
        if ($isDepartmentFiltered) {
            $totalCutiQuery->whereHas('employee', function ($query) use ($userDepartmentId) {
                $query->where('departemen_id', $userDepartmentId);
            });
        }

        // Filter berdasarkan shift jika dipilih
        $totalCutiQuery->when($selectedShift, function ($query) use ($selectedShift) {
            $query->whereHas('employee', function ($subQuery) use ($selectedShift) {
                $subQuery->where('shift_id', $selectedShift);
            });
        });

        $totalCuti = $totalCutiQuery->count();
        $stats[] = Stat::make('Total Cuti', $totalCuti)
            ->description('Pegawai yang sedang cuti')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('warning');

        // Widget khusus untuk kepala_lembaga dan admin: Statistik per departemen
        if (auth()->check() && in_array(auth()->user()->role, ['kepala_lembaga', 'admin'])) {
            $departmentStatsWidgets = array_map(function ($deptStat) {
                $hadir = $deptStat->attendance_count;
                $total = $deptStat->total_users;
                $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;

                $color = $persen >= 90 ? 'success' : ($persen >= 70 ? 'warning' : 'danger');

                return Stat::make($deptStat->name, "$hadir / $total")
                    ->description("$persen% hadir")
                    ->descriptionIcon($persen >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                    ->color($color);
            }, $departmentStats);

            // Tambahkan widget departemen ke stats
            $stats = array_merge($stats, $departmentStatsWidgets);
        }

        //Buatkan widget kosong disini
        // Pemisah horizontal
        // $stats[] = Stat::make(' ', ' ')->columnSpan('full');


        // Total umum
        // $stats[] = Stat::make('Total Pegawai', User::count())
        //     ->description('Seluruh karyawan')
        //     ->descriptionIcon('heroicon-m-users')
        //     ->color('primary');


        // //Buatkan widget total pegawai hadir pada hari aktif
        // $totalHadir = array_sum(array_column($departmentStats, 'attendance_count'));

        // $totalIzinQuery = \App\Models\Permit::whereDate('start_date', '<=', $selectedDate)
        //     ->whereDate('end_date', '>=', $selectedDate);

        // if ($selectedShift) {
        //     $totalIzinQuery->whereHas('employee', function ($query) use ($selectedShift) {
        //         $query->where('shift_id', $selectedShift);
        //     });
        // }

        // $totalIzin = $totalIzinQuery->count();
        // $stats[] = Stat::make('Total Izin', $totalIzin)
        //     ->description('Pegawai yang izin')
        //     ->descriptionIcon('heroicon-m-clipboard-document-check')
        //     ->color('info');

        // Total umum
        //Buatkan widget total pegawai hadir pada hari aktif




        // $stats[] = Stat::make('Total Jabatan', Jabatan::count())
        //     ->description('Jabatan tersedia')
        //     ->descriptionIcon('heroicon-m-briefcase')
        //     ->color('success');

        // $stats[] = Stat::make('Total Departemen', Departemen::count())
        //     ->description('Departemen aktif')
        //     ->descriptionIcon('heroicon-m-building-office')
        //     ->color('info');

        // Info tanggal aktif
        // $stats[] = Stat::make('Tanggal', $selectedDate->translatedFormat('d F Y'))
        //     ->description($selectedDate->isToday() ? 'Hari ini' : 'Filter aktif')
        //     ->descriptionIcon('heroicon-m-calendar-days')
        //     ->color('gray');

        /**
         * ----------------------------------------------------------------
         * KARTU INFORMASI FILTER AKTIF
         * ----------------------------------------------------------------
         * Menampilkan kartu statistik untuk memberikan informasi visual
         * mengenai filter tanggal dan shift yang sedang diterapkan.
         */

        // Kartu untuk menampilkan tanggal filter yang aktif.
        // $stats[] = Stat::make('Tanggal Filter', $selectedDate->format('d-m-Y'))
        //     ->description($selectedDate->isToday() ? 'Hari Ini' : 'Tanggal Terpilih')
        //     ->descriptionIcon('heroicon-m-calendar-days')
        //     ->color('gray');

        // // Kartu untuk menampilkan shift kerja yang aktif.
        // // Mengambil nama shift dari database jika ada shift yang dipilih.
        // $shiftName = 'Semua Shift';
        // $shiftStatus = 'Belum Dipilih';
        // if ($selectedShift) {
        //     // Cari model ShiftKerja berdasarkan ID yang ada di session.
        //     $shift = \App\Models\ShiftKerja::find($selectedShift);
        //     if ($shift) {
        //         $shiftName = $shift->name; // Gunakan nama shift jika ditemukan.
        //         $shiftStatus = 'Sudah Dipilih';
        //     }
        // }

        // $stats[] = Stat::make('Shift Kerja', $shiftName)
        //     ->description($shiftStatus)
        //     ->descriptionIcon('heroicon-m-clock')
        //     ->color('gray');

        return $stats;
    }

    // Auto refresh saat ada event dari Dashboard page
    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}
