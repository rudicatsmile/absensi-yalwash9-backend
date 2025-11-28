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

    protected function getStats(): array
    {
        $selectedDate = $this->getSelectedDate();

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


        $departmentStats = \Illuminate\Support\Facades\DB::select("
            SELECT
                d.id,
                d.name,
                COUNT(u.id) AS total_users,
                (SELECT COUNT(*) FROM attendances a WHERE a.departemen_id = d.id AND DATE(a.date) = ?) AS attendance_count
            FROM
                departemens d
            LEFT JOIN
                users u ON d.id = u.departemen_id
            GROUP BY
                d.id, d.name
            ORDER BY
                d.id
        ", [$selectedDate->toDateString()]);

        $stats = array_map(function ($deptStat) {
            $hadir = $deptStat->attendance_count;
            $total = $deptStat->total_users;
            $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;

            $color = $persen >= 90 ? 'success' : ($persen >= 70 ? 'warning' : 'danger');

            return Stat::make($deptStat->name, "$hadir / $total")
                ->description("$persen% hadir")
                ->descriptionIcon($persen >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($color);
        }, $departmentStats);

        //Buatkan widget kosong disini
        // Pemisah horizontal
        // $stats[] = Stat::make(' ', ' ')->columnSpan('full');


        // Total umum
        $stats[] = Stat::make('Total Pegawai', User::count())
            ->description('Seluruh karyawan')
            ->descriptionIcon('heroicon-m-users')
            ->color('primary');


        //Buatkan widget total pegawai hadir pada hari aktif
        $totalHadir = array_sum(array_column($departmentStats, 'attendance_count'));
        $stats[] = Stat::make('Total Hadir', $totalHadir)
            ->description('Pegawai yang hadir')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('primary');


        //Buatkan widget total pegawai tidak hadir pada hari aktif
        $totalTidakHadir = User::count() - $totalHadir;
        $stats[] = Stat::make('Total Tidak Hadir', $totalTidakHadir)
            ->description('Pegawai yang tidak hadir')
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');

        //Buatkan widget total pegawai izin pada hari aktif. AMbil dari tabel 'permits'. Range tanggal adalah dari $selectedDate sampai $selectedDate.
        $totalIzin = \App\Models\Permit::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->count();
        $stats[] = Stat::make('Total Izin', $totalIzin)
            ->description('Pegawai yang izin')
            ->descriptionIcon('heroicon-m-clipboard-document-check')
            ->color('info');

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

        return $stats;
    }

    // Auto refresh saat ada event dari Dashboard page
    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }
}
