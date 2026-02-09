<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\EmployeeWorkTimeSchedule;
use App\Models\Departemen;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class UserWorkScheduleReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $departmentId = null;
    public $search = '';
    public $readyToLoad = false;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function loadData()
    {
        $this->readyToLoad = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentId()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $departments = Departemen::all();

        $query = EmployeeWorkTimeSchedule::query()
            ->with(['user.departemen', 'shift', 'jamKerja']);

        if ($this->readyToLoad) {
            $query->whereBetween('schedule_date', [$this->startDate, $this->endDate]);

            if ($this->departmentId) {
                $query->whereHas('user', function ($q) {
                    $q->where('departemen_id', $this->departmentId);
                });
            }

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }
        } else {
            // Initial empty state or default to current month for performance if preferred,
            // but empty until "ready" is often better for modals.
            // However, to show *something*, let's show empty.
            $query->whereRaw('1 = 0');
        }

        $schedules = $query->orderBy('schedule_date')->paginate(10);

        return view('livewire.user-work-schedule-report', [
            'schedules' => $schedules,
            'departments' => $departments,
        ]);
    }

    public function exportExcel()
    {
        try {
            $query = EmployeeWorkTimeSchedule::query()
                ->with(['user.departemen', 'shift', 'jamKerja'])
                ->whereBetween('schedule_date', [$this->startDate, $this->endDate]);

            if ($this->departmentId) {
                $query->whereHas('user', function ($q) {
                    $q->where('departemen_id', $this->departmentId);
                });
            }

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $schedules = $query->orderBy('schedule_date')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['Tanggal', 'Nama Pegawai', 'Departemen', 'Shift', 'Jam Masuk', 'Jam Pulang', 'Total Jam'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);

            $row = 2;
            foreach ($schedules as $schedule) {
                $sheet->setCellValue('A' . $row, Carbon::parse($schedule->schedule_date)->format('d/m/Y'));
                $sheet->setCellValue('B' . $row, $schedule->user->name ?? '-');
                $sheet->setCellValue('C' . $row, $schedule->user->departemen->name ?? '-');
                $sheet->setCellValue('D' . $row, $schedule->shift->name ?? '-');
                $sheet->setCellValue('E' . $row, $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : '-');
                $sheet->setCellValue('F' . $row, $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : '-');
                
                $total = '-';
                if ($schedule->start_time && $schedule->end_time) {
                    $start = Carbon::parse($schedule->start_time);
                    $end = Carbon::parse($schedule->end_time);
                    $diff = $start->diffInMinutes($end);
                    $hours = floor($diff / 60);
                    $minutes = $diff % 60;
                    $total = sprintf('%02d:%02d', $hours, $minutes);
                }
                $sheet->setCellValue('G' . $row, $total);
                
                $row++;
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'laporan_jadwal_kerja_' . now()->format('YmdHis') . '.xlsx';
            
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename);

        } catch (\Exception $e) {
            Notification::make()->title('Gagal Export Excel')->body($e->getMessage())->danger()->send();
        }
    }

    public function exportPdf()
    {
        try {
            $query = EmployeeWorkTimeSchedule::query()
                ->with(['user.departemen', 'shift', 'jamKerja'])
                ->whereBetween('schedule_date', [$this->startDate, $this->endDate]);

            if ($this->departmentId) {
                $query->whereHas('user', function ($q) {
                    $q->where('departemen_id', $this->departmentId);
                });
            }

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $schedules = $query->orderBy('schedule_date')->get();

            $pdf = Pdf::loadView('pdf.work-schedule-report', [
                'schedules' => $schedules,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'laporan_jadwal_kerja_' . now()->format('YmdHis') . '.pdf');

        } catch (\Exception $e) {
            Notification::make()->title('Gagal Export PDF')->body($e->getMessage())->danger()->send();
        }
    }
}
