<?php

namespace App\Filament\Widgets;

use App\Services\Reports\AttendancePresenceService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Action;
use App\Filament\Pages\AttendancePresenceReport;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Departemen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PresenceSummaryWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

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

        return [
            Stat::make('Hadir', $present)
                ->description('Jumlah hari hadir')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(fn() => \Filament\Support\original_request()->fullUrlWithQuery(['action' => 'viewPresentEmployees']))
                ->action(
                    Action::make('viewPresentEmployees')
                        ->label('Lihat Pegawai Hadir')
                        ->modalHeading('Daftar Pegawai Hadir')
                        ->modalContent(fn() => new HtmlString($this->getPresentEmployeesTable()))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(Action $action) => $action->label('Tutup'))
                ),
            Stat::make('Izin', $absentByPermit)
                ->description('Jumlah hari berizin')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('info'),

            Stat::make('Tidak Hadir', $absentUnexcused)
                ->description('Jumlah hari tanpa keterangan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(fn() => \Filament\Support\original_request()->fullUrlWithQuery(['action' => 'viewAbsentEmployees']))
                ->action(
                    Action::make('viewAbsentEmployees')
                        ->label('Lihat Pegawai Tidak Hadir')
                        ->modalHeading('Daftar Pegawai Tidak Hadir')
                        ->modalContent(fn() => new HtmlString($this->getAbsentEmployeesTable()))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(Action $action) => $action->label('Tutup'))
                ),
        ];
    }

    protected function getPresentEmployeesTable(): string
    {
        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
        ];
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $table = '<div class="fi-table-con w-full max-w-full overflow-x-auto rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10" x-data=\'{"rows": [], "total": 0, "page": 1, "perPage": 10, "sort": "name", "dir": "asc", "q": "", "filters": ' . $filtersJson . ', "loading": false, "error": null, async "load"(){ try { this.loading=true; this.error=null; const base = { start_date: this.filters.start_date, end_date: this.filters.end_date, q: this.q, sort: this.sort, dir: this.dir, page: this.page, per_page: this.perPage }; if (this.filters.departemen_id !== null && this.filters.departemen_id !== undefined && this.filters.departemen_id !== "") { base.departemen_id = this.filters.departemen_id; } if (this.filters.shift_id !== null && this.filters.shift_id !== undefined && this.filters.shift_id !== "") { base.shift_id = this.filters.shift_id; } const params = new URLSearchParams(base); const url = window.location.origin + "/api/reports/present-employees?" + params.toString(); const res = await fetch(url); const json = await res.json(); if (!res.ok) { this.error = (json && json.message) ? json.message : "Gagal memuat"; this.rows = []; this.total = 0; } else { this.rows = json.data || []; this.total = (json.pagination && json.pagination.total) ? json.pagination.total : this.rows.length; } } catch(e) { this.error = "Gagal memuat"; this.rows = []; this.total = 0; } finally { this.loading=false; } }, "setSort"(key){ if(this.sort===key){ this.dir=this.dir==="asc"?"desc":"asc"; } else { this.sort=key; this.dir="asc"; } this.page=1; this.load(); }, "totalPages"(){ return Math.max(1, Math.ceil(this.total/this.perPage)); }, "goto"(p){ if(p<1||p>this.totalPages()) return; this.page=p; this.load(); }}\' x-init="load()">'
            . '<div class="flex items-center justify-between p-3">'
            . '<input type="text" class="fi-input text-sm w-64" placeholder="Cari nama..." x-model.debounce.300ms="q" @input="page=1; load()">'
            . '<div class="flex items-center gap-2">'
            . '<span class="text-sm">Baris per halaman</span>'
            . '<select class="fi-input text-sm" x-model.number="perPage" @change="page=1; load()">'
            . '<option value="10">10</option>'
            . '<option value="20">20</option>'
            . '<option value="50">50</option>'
            . '</select>'
            . '</div>'
            . '</div>'
            . '<table class="fi-table w-full min-w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">'
            . '<thead class="bg-gray-50 dark:bg-white/5">'
            . '<tr>'
            . '<th class="fi-table-header-cell p-3 text-left">No</th>'
            . '<th class="fi-table-header-cell p-3 text-left cursor-pointer" @click="setSort(\'name\')">Nama Pegawai <span x-show="sort===\'name\'" class="ml-1" x-text="dir===\'asc\'?\'↑\':\'↓\'"></span></th>'
            . '<th class="fi-table-header-cell p-3 text-left cursor-pointer" @click="setSort(\'departemen_name\')">Departemen <span x-show="sort===\'departemen_name\'" class="ml-1" x-text="dir===\'asc\'?\'↑\':\'↓\'"></span></th>'
            . '</tr>'
            . '</thead>'
            . '<tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">'
            . '<template x-for="(employee, index) in rows" :key="employee.id || (employee.name + index)">'
            . '<tr>'
            . '<td class="fi-table-cell p-3" x-text="(page-1)*perPage + index + 1"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.name"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.departemen_name"></td>'
            . '</tr>'
            . '</template>'
            . '</tbody>'
            . '</table>'
            . '<div class="flex items-center justify-between p-3">'
            . '<div class="flex items-center gap-2">'
            . '<button type="button" class="fi-btn px-2 py-1 text-sm" @click="goto(page-1)" :disabled="page<=1">Sebelumnya</button>'
            . '<span class="text-sm" x-text="page + \" / \" + totalPages()"></span>'
            . '<button type="button" class="fi-btn px-2 py-1 text-sm" @click="goto(page+1)" :disabled="page>=totalPages()">Berikutnya</button>'
            . '</div>'
            . '<span class="text-sm" x-show="loading">Memuat...</span>'
            . '<span class="text-sm text-red-600" x-show="error" x-text="error"></span>'
            . '</div>'
            . '</div>';

        return $table;
    }

    protected function getAbsentEmployeesTable(): string
    {
        $filters = [
            'start_date' => session('apr_start_date') ?? now()->toDateString(),
            'end_date' => session('apr_end_date') ?? now()->toDateString(),
            'departemen_id' => session('apr_departemen_id'),
            'shift_id' => session('apr_shift_id'),
        ];
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $table = '<div class="fi-table-con w-full max-w-full overflow-x-auto rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10" x-data=\'{"rows": [], "total": 0, "page": 1, "perPage": 10, "sort": "name", "dir": "asc", "q": "", "filters": ' . $filtersJson . ', "loading": false, "error": null, async "load"(){ try { this.loading=true; this.error=null; const base = { start_date: this.filters.start_date, end_date: this.filters.end_date, q: this.q, sort: this.sort, dir: this.dir, page: this.page, per_page: this.perPage }; if (this.filters.departemen_id !== null && this.filters.departemen_id !== undefined && this.filters.departemen_id !== "") { base.departemen_id = this.filters.departemen_id; } if (this.filters.shift_id !== null && this.filters.shift_id !== undefined && this.filters.shift_id !== "") { base.shift_id = this.filters.shift_id; } const params = new URLSearchParams(base); const url = window.location.origin + "/api/reports/absent-employees?" + params.toString(); const res = await fetch(url); const json = await res.json(); if (!res.ok) { this.error = (json && json.message) ? json.message : "Gagal memuat"; this.rows = []; this.total = 0; } else { this.rows = json.data || []; this.total = (json.pagination && json.pagination.total) ? json.pagination.total : this.rows.length; } } catch(e) { this.error = "Gagal memuat"; this.rows = []; this.total = 0; } finally { this.loading=false; } }, "setSort"(key){ if(this.sort===key){ this.dir=this.dir==="asc"?"desc":"asc"; } else { this.sort=key; this.dir="asc"; } this.page=1; this.load(); }, "totalPages"(){ return Math.max(1, Math.ceil(this.total/this.perPage)); }, "goto"(p){ if(p<1||p>this.totalPages()) return; this.page=p; this.load(); }}\' x-init="load()">'
            . '<div class="flex items-center justify-between p-3">'
            . '<input type="text" class="fi-input text-sm w-64" placeholder="Cari nama..." x-model.debounce.300ms="q" @input="page=1; load()">'
            . '<div class="flex items-center gap-2">'
            . '<span class="text-sm">Baris per halaman</span>'
            . '<select class="fi-input text-sm" x-model.number="perPage" @change="page=1; load()">'
            . '<option value="10">10</option>'
            . '<option value="20">20</option>'
            . '<option value="50">50</option>'
            . '</select>'
            . '</div>'
            . '</div>'
            . '<table class="fi-table w-full min-w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">'
            . '<thead class="bg-gray-50 dark:bg-white/5">'
            . '<tr>'
            . '<th class="fi-table-header-cell p-3 text-left">No</th>'
            . '<th class="fi-table-header-cell p-3 text-left cursor-pointer" @click="setSort(\'name\')">Nama Pegawai <span x-show="sort===\'name\'" class="ml-1" x-text="dir===\'asc\'?\'↑\':\'↓\'"></span></th>'
            . '<th class="fi-table-header-cell p-3 text-left cursor-pointer" @click="setSort(\'departemen_name\')">Departemen <span x-show="sort===\'departemen_name\'" class="ml-1" x-text="dir===\'asc\'?\'↑\':\'↓\'"></span></th>'
            . '<th class="fi-table-header-cell p-3 text-left cursor-pointer" @click="setSort(\'jabatan_name\')">Jabatan <span x-show="sort===\'jabatan_name\'" class="ml-1" x-text="dir===\'asc\'?\'↑\':\'↓\'"></span></th>'
            . '<th class="fi-table-header-cell p-3 text-left">Alasan</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">'
            . '<template x-for="(employee, index) in rows" :key="employee.id || (employee.name + index)">'
            . '<tr>'
            . '<td class="fi-table-cell p-3" x-text="(page-1)*perPage + index + 1"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.name"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.departemen_name"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.jabatan_name"></td>'
            . '<td class="fi-table-cell p-3" x-text="employee.reason || \"Alpa\""></td>'
            . '</tr>'
            . '</template>'
            . '</tbody>'
            . '</table>'
            . '<div class="flex items-center justify-between p-3">'
            . '<div class="flex items-center gap-2">'
            . '<button type="button" class="fi-btn px-2 py-1 text-sm" @click="goto(page-1)" :disabled="page<=1">Sebelumnya</button>'
            . '<span class="text-sm" x-text="page + \" / \" + totalPages()"></span>'
            . '<button type="button" class="fi-btn px-2 py-1 text-sm" @click="goto(page+1)" :disabled="page>=totalPages()">Berikutnya</button>'
            . '</div>'
            . '<span class="text-sm" x-show="loading">Memuat...</span>'
            . '<span class="text-sm text-red-600" x-show="error" x-text="error"></span>'
            . '</div>'
            . '</div>';

        return $table;
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
