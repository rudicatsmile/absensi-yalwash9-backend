<?php

namespace App\Filament\Widgets;

use App\Services\Reports\AttendancePresenceService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Pages\AttendancePresenceReport;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Departemen;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PresenceSummaryWidget extends BaseWidget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;
    protected string $view = 'filament.widgets.presence-summary-widget';

    protected function getStats(): array
    {
        $start = session('apr_start_date') ?? now()->toDateString();
        $end = session('apr_end_date') ?? now()->toDateString();
        $departemenId = session('apr_departemen_id');
        $shiftId = session('apr_shift_id');
        $status = session('apr_status') ?? 'semua';
        $mode = session('apr_mode') ?? 'check';

        $service = app(AttendancePresenceService::class);
        $matrix = $service->buildMatrix($start, $end, $departemenId, $shiftId, $status, $mode);
        $totals = $matrix['totals'] ?? ['present' => 0, 'absent' => 0, 'absent_by_permit' => 0, 'absent_unexcused' => 0];

        $present = (int) ($totals['present'] ?? 0);
        $absentByPermit = (int) ($totals['absent_by_permit'] ?? 0);
        $absentUnexcused = (int) ($totals['absent_unexcused'] ?? 0);

        //$absentByPermit     :   totalCutiRecords
        // Hitung Data Cuti
        $cutiQuery = Leave::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);

        if ($departemenId) {
            $cutiQuery->whereHas('employee', function ($q) use ($departemenId) {
                $q->where('departemen_id', $departemenId);
            });
        }

        if ($shiftId) {
            $cutiQuery->whereHas('employee', function ($q) use ($shiftId) {
                $q->where('shift_kerja_id', $shiftId);
            });
        }

        $totalCutiRecords = $cutiQuery->count();
        //$absentUnexcused = $absentUnexcused - $absentByPermit - $totalCutiRecords;
        $absentUnexcused = $absentUnexcused - $totalCutiRecords;
        $leaves = $cutiQuery->get();

        $totalCutiDays = 0;
        $periodStart = Carbon::parse($start);
        $periodEnd = Carbon::parse($end);
        // Total hari dalam periode (inklusif)
        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);

            // Cari irisan antara periode laporan dan periode cuti
            $overlapStart = $leaveStart->max($periodStart);
            $overlapEnd = $leaveEnd->min($periodEnd);

            if ($overlapStart <= $overlapEnd) {
                $totalCutiDays += $overlapStart->diffInDays($overlapEnd) + 1;
            }
        }

        // Hitung total user untuk penyebut persentase
        $userQuery = User::query();
        if ($departemenId) {
            $userQuery->where('departemen_id', $departemenId);
        }
        if ($shiftId) {
            $userQuery->where('shift_kerja_id', $shiftId);
        }
        $totalUsers = $userQuery->count();
        $totalPotentialDays = $totalUsers * $totalDaysInPeriod;

        $percentage = $totalPotentialDays > 0 ? ($totalCutiDays / $totalPotentialDays) * 100 : 0;
        $percentageFormatted = number_format($percentage, 1) . '%';

        return [
            Stat::make('Total Hadir', $present)
                ->description('Pegawai hadir')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes([
                    'class' => 'hover:scale-105 transition-transform duration-300 cursor-pointer text-black-header',
                    'wire:click' => "openPresentEmployees",
                    'title' => 'Lihat Daftar Pegawai Hadir',
                ]),

            Stat::make('Izin', $absentByPermit)
                ->description('Pegawai Izin')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('info')
                ->extraAttributes([
                    'class' => 'hover:scale-105 transition-transform duration-300 cursor-pointer',
                    'wire:click' => "openPermitEmployees",
                ]),

            Stat::make('Cuti', $totalCutiRecords)
                ->description("Total hari: $totalCutiDays ($percentageFormatted)")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform duration-300',
                    'title' => "Total Pengajuan: $totalCutiRecords\nTotal Hari Cuti: $totalCutiDays\nPersentase: $percentageFormatted dari total hari kerja ($totalPotentialDays hari)",
                    'wire:click' => "openLeaveEmployees",
                ]),

            Stat::make('Tidak Hadir', $absentUnexcused)
                ->description('Pegawai belum/tidak hadir')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'hover:scale-105 transition-transform duration-300 cursor-pointer',
                    'wire:click' => "openAbsentEmployees",
                ]),
        ];
    }

    public function openPresentEmployees(): void
    {
        $this->mountAction('viewPresentEmployees');
    }

    public function viewPresentEmployees(): Action
    {
        return Action::make('viewPresentEmployees')
            ->label('Lihat Pegawai Hadir')
            ->modalHeading('Daftar Pegawai Hadir')
            ->modalContent(new HtmlString($this->getPresentEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    public function openPermitEmployees(): void
    {
        $this->mountAction('viewPermitEmployees');
    }

    public function viewPermitEmployees(): Action
    {
        return Action::make('viewPermitEmployees')
            ->label('Lihat Pegawai Berizin')
            ->modalHeading('Daftar Pegawai Berizin')
            ->modalContent(new HtmlString($this->getPermitEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    public function openLeaveEmployees(): void
    {
        $this->mountAction('viewLeaveEmployees');
    }

    public function viewLeaveEmployees(): Action
    {
        return Action::make('viewLeaveEmployees')
            ->label('Lihat Pegawai Cuti')
            ->modalHeading('Daftar Pegawai Sedang Cuti')
            ->modalContent(new HtmlString($this->getLeaveEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    public function openAbsentEmployees(): void
    {
        $this->mountAction('viewAbsentEmployees');
    }

    public function viewAbsentEmployees(): Action
    {
        return Action::make('viewAbsentEmployees')
            ->label('Lihat Pegawai Tidak Hadir')
            ->modalHeading('Daftar Pegawai Tidak Hadir')
            ->modalContent(new HtmlString($this->getAbsentEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    protected function getPresentEmployeesTable(): string
    {
        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
        ];

        $columns = [
            ['key' => 'name', 'label' => 'Nama Pegawai'],
            ['key' => 'departemen_name', 'label' => 'Departemen'],
        ];

        return view('filament.widgets.api-table', [
            'endpoint' => '/api/reports/present-employees',
            'columns' => $columns,
            'filters' => $filters,
        ])->render();
    }

    protected function getAbsentEmployeesTable(): string
    {
        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
        ];

        $columns = [
            ['key' => 'name', 'label' => 'Nama Pegawai'],
            ['key' => 'departemen_name', 'label' => 'Departemen'],
            ['key' => 'jabatan_name', 'label' => 'Jabatan'],
            ['key' => 'reason', 'label' => 'Alasan'],
        ];

        return view('filament.widgets.api-table', [
            'endpoint' => '/api/reports/absent-employees',
            'columns' => $columns,
            'filters' => $filters,
        ])->render();
    }

    protected function getPermitEmployeesTable(): string
    {
        $permitTypes = \App\Models\PermitType::pluck('name', 'id')->toArray();

        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
            'permit_type_id' => null,
            'status' => null,
        ];

        $columns = [
            ['key' => 'employee_name', 'label' => 'Nama Pegawai'],
            ['key' => 'permit_type', 'label' => 'Jenis Izin'],
            ['key' => 'reason', 'label' => 'Alasan'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
        ];

        $filterOptions = [
            [
                'type' => 'select',
                'model' => 'permit_type_id',
                'placeholder' => 'Semua Jenis Izin',
                'options' => $permitTypes
            ],
            [
                'type' => 'select',
                'model' => 'status',
                'placeholder' => 'Semua Status',
                'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']
            ]
        ];

        return view('filament.widgets.api-table', [
            'endpoint' => '/api/reports/permit-employees',
            'columns' => $columns,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ])->render();
    }

    protected function getLeaveEmployeesTable(): string
    {
        $leaveTypes = \App\Models\LeaveType::pluck('name', 'id')->toArray();

        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
            'leave_type_id' => null,
        ];

        $columns = [
            ['key' => 'employee_name', 'label' => 'Nama Pegawai'],
            ['key' => 'leave_type', 'label' => 'Jenis Cuti'],
            ['key' => 'start_date', 'label' => 'Mulai', 'format' => 'date'],
            ['key' => 'end_date', 'label' => 'Selesai', 'format' => 'date'],
        ];

        $filterOptions = [
            [
                'type' => 'select',
                'model' => 'leave_type_id',
                'placeholder' => 'Semua Jenis Cuti',
                'options' => $leaveTypes
            ]
        ];

        return view('filament.widgets.api-table', [
            'endpoint' => '/api/reports/leave-employees',
            'columns' => $columns,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ])->render();
    }

    protected function getPresentEmployeesData()
    {
        $start = session('apr_start_date') ?? now()->toDateString();
        $end = session('apr_end_date') ?? now()->toDateString();
        $departemenId = session('apr_departemen_id');
        $shiftId = session('apr_shift_id');

        return User::query()
            ->select([
                'users.id',
                'users.name',
                'departemens.name as departemen_name',
                DB::raw('COUNT(DISTINCT attendances.date) as total_hadir')
            ])
            ->join('attendances', 'users.id', '=', 'attendances.user_id')
            ->join('departemens', 'users.departemen_id', '=', 'departemens.id')
            ->whereBetween('attendances.date', [$start, $end])
            ->when($departemenId, fn($q) => $q->where('users.departemen_id', $departemenId))
            ->when($shiftId, fn($q) => $q->where('attendances.shift_id', $shiftId))
            ->groupBy('users.id', 'users.name', 'departemens.name')
            ->orderBy('departemens.id')
            ->orderBy('users.name')
            ->get();
    }

    protected function getListeners(): array
    {
        return ['refresh-widgets' => '$refresh'];
    }


}
