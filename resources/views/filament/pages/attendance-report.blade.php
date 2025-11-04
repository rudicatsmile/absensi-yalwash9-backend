@push('styles')
    @vite('resources/css/app.css')
    <style>
        /* Custom styles for better cross-browser compatibility */
        .attendance-table {
            font-feature-settings: "tnum";
        }

        .status-badge {
            font-weight: 500;
            letter-spacing: 0.025em;
        }

        /* Smooth scrolling for table */
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush

<x-filament-panels::page>
    <div class="max-w-7xl mx-auto space-y-6" role="main" aria-label="Laporan Kehadiran">
        {{-- Header Section --}}
        <header class="bg-gradient-to-r from-slate-700 to-slate-800 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-8 sm:px-8 md:px-10">
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Laporan Kehadiran</h1>
                <p class="text-slate-300 text-sm sm:text-base">Kelola dan pantau kehadiran karyawan dengan mudah</p>
            </div>
        </header>

        {{-- Filter Section --}}
        <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="filter-heading">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h2 id="filter-heading" class="text-lg font-semibold text-slate-900">Filter Laporan</h2>
                    <span class="inline-flex px-3 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-full self-start sm:self-auto">
                        Periode Aktif
                    </span>
                </div>
                <div class="bg-slate-50 rounded-lg p-4">
                    {{ $this->form }}
                </div>
            </div>

            {{-- Statistik Ringkas --}}
            @if(isset($summary))
            <div class="px-6 pb-6 space-y-4">
                <h3 class="text-base font-medium text-slate-900 mb-4">Statistik Ringkas</h3>

                {{-- Basic Statistics --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                        <div class="text-sm font-medium text-slate-500 mb-1">Total Records</div>
                        <div class="text-2xl font-semibold text-slate-900">{{ $summary['total_records'] }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                        <div class="text-sm font-medium text-slate-500 mb-1">Total Karyawan</div>
                        <div class="text-2xl font-semibold text-slate-900">{{ $summary['total_users'] }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 sm:col-span-2 lg:col-span-1">
                        <div class="text-sm font-medium text-slate-500 mb-1">Jumlah Hari</div>
                        <div class="text-2xl font-semibold text-slate-900">{{ $summary['days_count'] }}</div>
                    </div>
                </div>

                {{-- Status Statistics --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-yellow-700 mb-1">Keterlambatan</div>
                        <div class="text-2xl font-semibold text-yellow-800">{{ $summary['late_count'] }}</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-red-700 mb-1">Pulang Cepat</div>
                        <div class="text-2xl font-semibold text-red-800">{{ $summary['early_leave_count'] }}</div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 sm:col-span-2 lg:col-span-1">
                        <div class="text-sm font-medium text-blue-700 mb-1">Kerja saat Libur</div>
                        <div class="text-2xl font-semibold text-blue-800">{{ $summary['holiday_work_count'] }}</div>
                    </div>
                </div>
            </div>
            @endif
        </section>

        {{-- Tabel Laporan Kehadiran --}}
        <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="report-heading">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 id="report-heading" class="text-lg font-semibold text-slate-900 mb-1">Laporan Kehadiran</h2>
                <p class="text-sm text-slate-600">
                    Periode: {{ $this->start_date }} s/d {{ $this->end_date }}
                    @if($this->shift_id) | Shift: {{ optional($attendances->first()?->shift)->name ?? '—' }} @endif
                </p>
            </div>

            @if(isset($attendances) && $attendances->count())
                {{-- Mobile Cards Layout --}}
                {{-- <div class="md:hidden space-y-3 p-4" role="list" aria-label="Daftar kehadiran karyawan">
                    @foreach ($attendances as $i => $a)
                        <article class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden" role="listitem">
                            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-200">
                                <h3 class="text-base font-semibold text-slate-900">{{ $a->user->name ?? '—' }}</h3>
                                @php
                                    $status = 'Hadir';
                                    $statusClass = 'bg-green-100 text-green-800';
                                    if (!$a->time_in) {
                                        $status = 'Tidak Masuk';
                                        $statusClass = 'bg-red-100 text-red-800';
                                    } elseif (!$a->time_out) {
                                        $status = 'Belum Pulang';
                                        $statusClass = 'bg-amber-100 text-amber-800';
                                    }
                                @endphp
                                <span class="status-badge px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}"
                                      aria-label="Status kehadiran: {{ $status }}">
                                    {{ $status }}
                                </span>
                            </div>
                            <div class="px-4 py-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Tanggal</dt>
                                    <dd class="text-slate-900">{{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Shift</dt>
                                    <dd class="text-slate-900">{{ $a->shift->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Jam Masuk</dt>
                                    <dd class="text-slate-900 font-mono">{{ $a->time_in ? \Carbon\Carbon::parse($a->time_in)->format('H:i') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Jam Keluar</dt>
                                    <dd class="text-slate-900 font-mono">{{ $a->time_out ? \Carbon\Carbon::parse($a->time_out)->format('H:i') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Telat (mnt)</dt>
                                    <dd class="text-slate-900 font-mono">{{ $a->late_minutes ?? 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 mb-1">Pulang Cepat (mnt)</dt>
                                    <dd class="text-slate-900 font-mono">{{ $a->early_leave_minutes ?? 0 }}</dd>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div> --}}

                {{-- Desktop Table Layout (Dinamis) --}}
                {{--
                    Dokumentasi Tampilan Dinamis
                    Parameter didukung (query atau variabel view):
                    - report_mode: 'detail' | 'matrix' (default 'detail')
                    - api_rows: array dari ReportController::attendanceReport (opsional untuk mode 'matrix')
                    Fallback: jika mode 'matrix' dipilih namun data tidak valid/tidak tersedia, otomatis kembali ke mode 'detail'.
                    Batasan: mode 'matrix' memerlukan kolom tanggal (DD-MM-YYYY) + kolom 'No', 'Nama_Pegawai', 'Total_kehadiran', 'Total_tidak_hadir', 'Total_jam_kerja'.
                    Contoh: /admin/attendance-report?report_mode=matrix
                --}}
                <div class="hidden md:block">
                    @php
                        $reportMode = $report_mode ?? request()->query('report_mode', 'detail');
                        $apiRows = $api_rows ?? (isset($rows) ? $rows : null);
                        $isMatrix = $reportMode === 'matrix' && is_array($apiRows) && !empty($apiRows);
                        $dateColumns = [];

                        if ($isMatrix) {
                            $first = (array) $apiRows[0];
                            foreach (array_keys($first) as $key) {
                                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', (string) $key)) {
                                    $dateColumns[] = $key;
                                }
                            }
                            sort($dateColumns);
                            $hasSummary = isset($first['Total_kehadiran'], $first['Total_tidak_hadir'], $first['Total_jam_kerja']);
                            $hasIdentity = isset($first['No']) && (isset($first['Nama_Pegawai']) || isset($first['Nama']));
                            if (!$hasIdentity || empty($dateColumns) || !$hasSummary) {
                                $isMatrix = false; // fallback ke detail
                            }
                        }
                    @endphp

                    @if($isMatrix)
                        <div class="table-container overflow-x-auto" role="region" aria-label="Tabel laporan kehadiran (matrix)" tabindex="0">
                            <table class="attendance-table min-w-full border border-slate-200 rounded-md" role="table">
                                <thead class="bg-slate-50 sticky top-0 z-10 border-b border-slate-200">
                                    <tr role="row">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">No</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Nama</th>
                                        @foreach($dateColumns as $col)
                                            <th scope="col" class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">{{ $col }}</th>
                                        @endforeach
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Total Hadir</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Total Tidak Hadir</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Total Jam Kerja</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach($apiRows as $row)
                                        @php $row = (array) $row; @endphp
                                        <tr class="hover:bg-slate-50 transition-colors duration-150" role="row">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $row['No'] ?? '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-900 border-b border-r last:border-r-0 border-slate-200">{{ $row['Nama_Pegawai'] ?? $row['Nama'] ?? '—' }}</td>
                                            @foreach($dateColumns as $col)
                                                @php
                                                    $val = $row[$col] ?? '-';
                                                    $badge = 'bg-slate-100 text-slate-700';
                                                    if ($val === 'O') $badge = 'bg-green-100 text-green-800';
                                                    elseif ($val === 'L') $badge = 'bg-yellow-100 text-yellow-800';
                                                    elseif ($val === 'A') $badge = 'bg-red-100 text-red-800';
                                                    elseif ($val === 'X') $badge = 'bg-blue-100 text-blue-800';
                                                @endphp
                                                <td class="px-3 py-2 text-center text-xs border-b border-r last:border-r-0 border-slate-200">
                                                    <span class="status-badge inline-flex px-2 py-0.5 rounded-full font-medium {{ $badge }}" aria-label="Status: {{ $val }}">{{ $val }}</span>
                                                </td>
                                            @endforeach
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $row['Total_kehadiran'] ?? 0 }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $row['Total_tidak_hadir'] ?? 0 }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $row['Total_jam_kerja'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="table-container overflow-x-auto" role="region" aria-label="Tabel laporan kehadiran" tabindex="0">
                            <table class="attendance-table min-w-full border border-slate-200 rounded-md" role="table">
                                <thead class="bg-slate-50 sticky top-0 z-10 border-b border-slate-200">
                                    <tr role="row">
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">No</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Nama</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Shift</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Tanggal</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Jam Masuk</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Jam Keluar</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Status</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Telat (mnt)</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">Pulang Cepat (mnt)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach ($attendances as $i => $a)
                                        <tr class="hover:bg-slate-50 transition-colors duration-150" role="row">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $i + 1 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 border-b border-r last:border-r-0 border-slate-200">{{ $a->user->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 border-b border-r last:border-r-0 border-slate-200">{{ $a->shift->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono border-b border-r last:border-r-0 border-slate-200">{{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono border-b border-r last:border-r-0 border-slate-200">{{ $a->time_in ? \Carbon\Carbon::parse($a->time_in)->format('H:i') : '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono border-b border-r last:border-r-0 border-slate-200">{{ $a->time_out ? \Carbon\Carbon::parse($a->time_out)->format('H:i') : '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-r last:border-r-0 border-slate-200">
                                                @php
                                                    $status = 'Hadir';
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    if (!$a->time_in) {
                                                        $status = 'Tidak Masuk';
                                                        $statusClass = 'bg-red-100 text-red-800';
                                                    } elseif (!$a->time_out) {
                                                        $status = 'Belum Pulang';
                                                        $statusClass = 'bg-amber-100 text-amber-800';
                                                    }
                                                @endphp
                                                <span class="status-badge inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}"
                                                      aria-label="Status kehadiran: {{ $status }}">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono border-b border-r last:border-r-0 border-slate-200">{{ $a->late_minutes ?? 0 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono border-b border-r last:border-r-0 border-slate-200">{{ $a->early_leave_minutes ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-8 text-center" role="status" aria-live="polite">
                    <div class="mx-auto w-16 h-16 mb-4 text-slate-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">Tidak ada data</h3>
                    <p class="text-sm text-slate-600">Tidak ada data kehadiran untuk periode yang dipilih.</p>
                </div>
            @endif
        </section>

        {{-- Ringkasan Periode --}}
        @if($this->start_date && $this->end_date)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="summary-heading">
            <div class="p-6">
                <h2 id="summary-heading" class="text-lg font-semibold text-slate-900 mb-4">Ringkasan Periode</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                        <dt class="text-sm font-medium text-slate-500 mb-1">Periode</dt>
                        <dd class="text-base font-semibold text-slate-900">
                            {{ \Carbon\Carbon::parse($this->start_date)->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse($this->end_date)->format('d M Y') }}
                        </dd>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                        <dt class="text-sm font-medium text-slate-500 mb-1">Total Hari</dt>
                        <dd class="text-base font-semibold text-slate-900">
                            {{ \Carbon\Carbon::parse($this->start_date)->diffInDays(\Carbon\Carbon::parse($this->end_date)) + 1 }} hari
                        </dd>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 sm:col-span-2 lg:col-span-1">
                        <dt class="text-sm font-medium text-slate-500 mb-1">Shift</dt>
                        <dd class="text-base font-semibold text-slate-900">
                            @if($this->shift_id)
                                {{ \App\Models\ShiftKerja::find($this->shift_id)?->name ?? 'Tidak ditemukan' }}
                            @else
                                Semua Shift
                            @endif
                        </dd>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- Informasi Tambahan --}}
        <aside class="bg-blue-50 border border-blue-200 rounded-xl p-6" aria-labelledby="info-heading">
            <div class="flex items-start">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 id="info-heading" class="text-sm font-semibold text-blue-900 mb-2">Informasi Laporan</h3>
                    <ul class="text-sm text-blue-800 space-y-1" role="list">
                        <li>• Pilih rentang tanggal untuk periode laporan yang diinginkan</li>
                        <li>• Filter berdasarkan shift tertentu atau pilih semua shift</li>
                        <li>• Klik tombol Export untuk mengunduh laporan dalam format CSV atau Excel</li>
                        <li>• Laporan akan mencakup data kehadiran, keterlambatan, dan lembur</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
