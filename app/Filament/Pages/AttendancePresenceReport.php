<?php

namespace App\Filament\Pages;

use App\Models\Departemen;
use App\Models\ShiftKerja;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Filament\Widgets\AbsenceBreakdownChartWidget;
use App\Filament\Widgets\AttendancePresenceMatrixWidget;
use App\Filament\Widgets\PresenceChartWidget;
use App\Filament\Widgets\PresenceSummaryWidget;
use UnitEnum;
use BackedEnum;

class AttendancePresenceReport extends Page
{
    protected static ?string $navigationLabel = 'Laporan Kehadiran & Tidak Hadir';
    protected static ?string $title = 'Laporan Kehadiran & Tidak Hadir';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 11;

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $departemen_id = null;
    public ?int $shift_id = null;
    public ?string $status = 'semua';
    public ?string $mode = 'check';

    // Tidak menggunakan blade kustom; konten ditampilkan melalui widgets & actions

    public function mount(): void
    {
        $this->start_date = session('apr_start_date') ?? $this->start_date ?? now()->toDateString();
        $this->end_date = session('apr_end_date') ?? $this->end_date ?? now()->toDateString();
        $this->departemen_id = session('apr_departemen_id') ?? ((auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) ? auth()->user()->departemen_id : null);
        $this->shift_id = session('apr_shift_id') ?? $this->shift_id;
        $this->status = session('apr_status') ?? $this->status ?? 'semua';
        $this->mode = session('apr_mode') ?? $this->mode ?? 'check';
        $this->syncFiltersToSession();
    }

    protected function syncFiltersToSession(): void
    {
        session([
            'apr_start_date' => $this->start_date,
            'apr_end_date' => $this->end_date,
            'apr_departemen_id' => $this->departemen_id,
            'apr_shift_id' => $this->shift_id,
            'apr_status' => $this->status,
            'apr_mode' => $this->mode,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Filter')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->modalHeading('Filter Laporan')
                ->form([
                        DatePicker::make('start_date')->label('Tanggal Mulai')->native(false)->displayFormat('d-m-Y')->default($this->start_date),
                        DatePicker::make('end_date')->label('Tanggal Akhir')->native(false)->displayFormat('d-m-Y')->default($this->end_date),
                        Select::make('departemen_id')->label('Departemen')->options(function () {
                            $base = Departemen::query()->orderBy('id');
                            if (auth()->check() && in_array(auth()->user()->role, ['manager', 'kepala_sub_bagian'], true)) {
                                $base->whereKey(auth()->user()->departemen_id);
                            }
                            return [null => 'Semua Departemen'] + $base->pluck('name', 'id')->toArray();
                        })->searchable()->native(false)->default($this->departemen_id),
                        Select::make('shift_id')->label('Shift Kerja')->options(fn() => [null => 'Semua Shift'] + ShiftKerja::query()->orderBy('name')->pluck('name', 'id')->toArray())->searchable()->native(false)->default($this->shift_id),
                        ToggleButtons::make('status')->label('Status')->options(['semua' => 'Semua', 'Hadir' => 'Hadir', 'Tidak Hadir' => 'Tidak Hadir'])->inline()->default($this->status),
                        ToggleButtons::make('mode')->label('Tampilan Sel')->options(['check' => 'Check', 'jumlah shift' => 'Jumlah Shift'])->inline()->default($this->mode),
                    ])
                ->action(function (array $data): void {
                    $this->start_date = $data['start_date'] ?? $this->start_date;
                    $this->end_date = $data['end_date'] ?? $this->end_date;
                    $this->departemen_id = $data['departemen_id'] ?? $this->departemen_id;
                    $this->shift_id = $data['shift_id'] ?? $this->shift_id;
                    $this->status = $data['status'] ?? $this->status;
                    $this->mode = $data['mode'] ?? $this->mode;
                    $this->syncFiltersToSession();
                    $this->dispatch('refresh-widgets');
                }),

            Action::make('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => $this->buildApiUrl('xlsx'))
                ->openUrlInNewTab(),

            Action::make('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn() => $this->buildApiUrl('pdf'))
                ->openUrlInNewTab(),
        ];
    }

    protected function buildApiUrl(?string $export = null): string
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'departemen_id' => $this->departemen_id,
            'shift_id' => $this->shift_id,
            'status' => $this->status,
            'mode' => $this->mode,
        ];

        Validator::make($data, [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:semua,Hadir,Tidak Hadir'],
            'mode' => ['required', 'in:check,jumlah shift'],
        ])->validate();

        if ($export !== null) {
            $data['format'] = $export;
        }
        $query = http_build_query(array_filter($data, fn($v) => $v !== null && $v !== ''));
        $url = url('/api/reports/attendance-presence') . '?' . $query;
        return $url;
    }

    protected function getViewData(): array
    {
        $service = app(\App\Services\Reports\AttendancePresenceService::class);
        $result = $service->buildMatrix(
            $this->start_date,
            $this->end_date,
            $this->departemen_id,
            $this->shift_id,
            $this->status ?? 'semua',
            $this->mode ?? 'check'
        );

        return [
            'matrix' => $result,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PresenceSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AttendancePresenceMatrixWidget::class,
            PresenceChartWidget::class,
            AbsenceBreakdownChartWidget::class,

        ];
    }

    public function viewPresentEmployeesAction(): Action
    {
        return Action::make('viewPresentEmployees')
            ->label('Lihat Pegawai Hadir')
            ->modalHeading('Daftar Pegawai Hadir')
            ->modalContent(new HtmlString($this->getPresentEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    public function viewAbsentEmployeesAction(): Action
    {
        return Action::make('viewAbsentEmployees')
            ->label('Lihat Pegawai Tidak Hadir')
            ->modalHeading('Daftar Pegawai Tidak Hadir')
            ->modalContent(new HtmlString($this->getAbsentEmployeesTable()))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(Action $action) => $action->label('Tutup'));
    }

    protected function getPresentEmployeesData()
    {
        $start = $this->start_date ?? now()->toDateString();
        $end = $this->end_date ?? now()->toDateString();
        $departemenId = $this->departemen_id;
        $shiftId = $this->shift_id;

        return User::query()
            ->select([
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(DISTINCT attendances.date) as total_hadir'),
                    DB::raw('(SELECT name FROM departemens WHERE departemens.id = users.departemen_id) as departemen_name'),
                ])
            ->join('attendances', 'users.id', '=', 'attendances.user_id')
            ->whereBetween('attendances.date', [$start, $end])
            ->when($departemenId, fn($q) => $q->where('users.departemen_id', $departemenId))
            ->when($shiftId, fn($q) => $q->where('attendances.shift_id', $shiftId))
            // Filter tambahan: hanya status hadir 'on_time' atau 'late'
            // ->whereIn('attendances.status', ['on_time', 'late'])
            ->groupBy('users.id', 'users.name', 'users.departemen_id')
            ->orderBy('users.departemen_id')
            ->orderBy('users.name')
            ->get();
    }

    protected function getPresentEmployeesTable(): string
    {
        $filters = [
            'start_date' => $this->start_date ?? now()->toDateString(),
            'end_date' => $this->end_date ?? now()->toDateString(),
            'departemen_id' => $this->departemen_id,
            'shift_id' => $this->shift_id,
        ];
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Styling: gunakan Tailwind utilitas untuk input & tombol agar konsisten di modal HTML,
        // tetap gunakan kerangka tabel Filament (fi-table-con, fi-table) untuk nuansa bawaan.
        $table = '<div class="fi-table-con w-full max-w-full overflow-x-auto rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10" x-data=\'{"rows": [], "total": 0, "page": 1, "perPage": 10, "sort": "name", "dir": "asc", "q": "", "filters": ' . $filtersJson . ', "loading": false, "error": null, async "load"(){ try { this.loading=true; this.error=null; const base = { start_date: this.filters.start_date, end_date: this.filters.end_date, q: this.q, sort: this.sort, dir: this.dir, page: this.page, per_page: this.perPage }; if (this.filters.departemen_id !== null && this.filters.departemen_id !== undefined && this.filters.departemen_id !== "") { base.departemen_id = this.filters.departemen_id; } if (this.filters.shift_id !== null && this.filters.shift_id !== undefined && this.filters.shift_id !== "") { base.shift_id = this.filters.shift_id; } const params = new URLSearchParams(base); const url = window.location.origin + "/api/reports/present-employees?" + params.toString(); const res = await fetch(url); const json = await res.json(); if (!res.ok) { this.error = (json && json.message) ? json.message : "Gagal memuat"; this.rows = []; this.total = 0; } else { this.rows = json.data || []; this.total = (json.pagination && json.pagination.total) ? json.pagination.total : this.rows.length; } } catch(e) { this.error = "Gagal memuat"; this.rows = []; this.total = 0; } finally { this.loading=false; } }, "setSort"(key){ if(this.sort===key){ this.dir=this.dir==="asc"?"desc":"asc"; } else { this.sort=key; this.dir="asc"; } this.page=1; this.load(); }, "totalPages"(){ return Math.max(1, Math.ceil(this.total/this.perPage)); }, "goto"(p){ if(p<1||p>this.totalPages()) return; this.page=p; this.load(); }}\' x-init="load()">'
            . '<div class="flex items-center justify-between p-3">'
            . '<input type="text" class="w-64 px-2 py-1 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:border-white/10" placeholder="Cari nama..." x-model.debounce.300ms="q" @input="page=1; load()">'
            . '<div class="flex items-center gap-2">'
            . '<span class="text-sm">Baris per halaman</span>'
            . '<select class="px-2 py-1 text-sm border rounded-md dark:bg-gray-900 dark:border-white/10" x-model.number="perPage" @change="page=1; load()">'
            . '<option value="10">10</option>'
            . '<option value="20">20</option>'
            . '<option value="50">50</option>'
            . '</select>'
            . '</div>'
            . '</div>'
            . '<table class="fi-table w-full min-w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">'
            . '<thead class="bg-gray-50 dark:bg-white/5">'
            . '<tr>'
            . '<th class="fi-table-header-cell p-3 text-left">No.</th>'
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
            . '<button type="button" class="px-2 py-1 text-sm border rounded-md bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:border-white/10" @click="goto(page-1)" :disabled="page<=1">Sebelumnya</button>'
            . '<span class="text-sm" x-text="page + \" / \" + totalPages()"></span>'
            . '<button type="button" class="px-2 py-1 text-sm border rounded-md bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:border-white/10" @click="goto(page+1)" :disabled="page>=totalPages()">Berikutnya</button>'
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
            'start_date' => $this->start_date ?? now()->toDateString(),
            'end_date' => $this->end_date ?? now()->toDateString(),
            'departemen_id' => $this->departemen_id,
            'shift_id' => $this->shift_id,
        ];
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $table = '<div class="fi-table-con w-full max-w-full overflow-x-auto rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10" x-data=\'{"rows": [], "total": 0, "page": 1, "perPage": 10, "sort": "name", "dir": "asc", "q": "", "filters": ' . $filtersJson . ', "loading": false, "error": null, async "load"(){ try { this.loading=true; this.error=null; const base = { start_date: this.filters.start_date, end_date: this.filters.end_date, q: this.q, sort: this.sort, dir: this.dir, page: this.page, per_page: this.perPage }; if (this.filters.departemen_id !== null && this.filters.departemen_id !== undefined && this.filters.departemen_id !== "") { base.departemen_id = this.filters.departemen_id; } if (this.filters.shift_id !== null && this.filters.shift_id !== undefined && this.filters.shift_id !== "") { base.shift_id = this.filters.shift_id; } const params = new URLSearchParams(base); const url = window.location.origin + "/api/reports/absent-employees?" + params.toString(); const res = await fetch(url); const json = await res.json(); if (!res.ok) { this.error = (json && json.message) ? json.message : "Gagal memuat"; this.rows = []; this.total = 0; } else { this.rows = json.data || []; this.total = (json.pagination && json.pagination.total) ? json.pagination.total : this.rows.length; } } catch(e) { this.error = "Gagal memuat"; this.rows = []; this.total = 0; } finally { this.loading=false; } }, "setSort"(key){ if(this.sort===key){ this.dir=this.dir==="asc"?"desc":"asc"; } else { this.sort=key; this.dir="asc"; } this.page=1; this.load(); }, "totalPages"(){ return Math.max(1, Math.ceil(this.total/this.perPage)); }, "goto"(p){ if(p<1||p>this.totalPages()) return; this.page=p; this.load(); }}\' x-init="load()">'
            . '<div class="flex items-center justify-between p-3">'
            . '<input type="text" class="w-64 px-2 py-1 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:border-white/10" placeholder="Cari nama..." x-model.debounce.300ms="q" @input="page=1; load()">'
            . '<div class="flex items-center gap-2">'
            . '<span class="text-sm">Baris per halaman</span>'
            . '<select class="px-2 py-1 text-sm border rounded-md dark:bg-gray-900 dark:border-white/10" x-model.number="perPage" @change="page=1; load()">'
            . '<option value="10">10</option>'
            . '<option value="20">20</option>'
            . '<option value="50">50</option>'
            . '</select>'
            . '</div>'
            . '</div>'
            . '<table class="fi-table w-full min-w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">'
            . '<thead class="bg-gray-50 dark:bg-white/5">'
            . '<tr>'
            . '<th class="fi-table-header-cell p-3 text-left">No.</th>'
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
            . '<button type="button" class="px-2 py-1 text-sm border rounded-md bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:border-white/10" @click="goto(page-1)" :disabled="page<=1">Sebelumnya</button>'
            . '<span class="text-sm" x-text="page + \" / \" + totalPages()"></span>'
            . '<button type="button" class="px-2 py-1 text-sm border rounded-md bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:border-white/10" @click="goto(page+1)" :disabled="page>=totalPages()">Berikutnya</button>'
            . '</div>'
            . '<span class="text-sm" x-show="loading">Memuat...</span>'
            . '<span class="text-sm text-red-600" x-show="error" x-text="error"></span>'
            . '</div>'
            . '</div>';

        return $table;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role !== 'employee';
    }
}
