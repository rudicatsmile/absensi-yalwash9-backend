@push('styles')
    @vite('resources/css/app.css')
@endpush

<div class="max-w-7xl mx-auto space-y-6" role="main" aria-label="Laporan Kehadiran & Tidak Hadir">
    <header class="bg-gradient-to-r from-slate-700 to-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-8 sm:px-8 md:px-10">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Laporan Kehadiran & Tidak Hadir</h1>
            <p class="text-slate-300 text-sm sm:text-base">Pantau kehadiran dan ketidakhadiran pegawai berdasarkan rentang tanggal</p>
        </div>
    </header>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="filter-heading">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h2 id="filter-heading" class="text-lg font-semibold text-slate-900">Filter</h2>
                <span class="inline-flex px-3 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-full">
                    Periode Aktif
                </span>
            </div>
            <div class="bg-slate-50 rounded-lg p-4">
                {{ $this->form }}
            </div>
        </div>
    </section>

    @php
        $dates = $matrix['dates'] ?? [];
        $rows = $matrix['rows'] ?? [];
        $mode = $matrix['mode'] ?? 'check';
        $totals = $matrix['totals'] ?? ['present' => 0, 'absent' => 0, 'absent_by_permit' => 0, 'absent_unexcused' => 0];
    @endphp

    <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="report-heading">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 id="report-heading" class="text-lg font-semibold text-slate-900 mb-1">Matriks Kehadiran per Tanggal</h2>
            <p class="text-sm text-slate-600">Periode: {{ $this->start_date }} s/d {{ $this->end_date }}</p>
            @if(($this->status ?? 'semua') === 'semua' && !empty($rows))
                @php
                    $presentEmployees = 0; $excusedEmployees = 0; $absentEmployees = 0;
                    foreach ($rows as $row) {
                        $hasPresent = false; $hasExcused = false; $hasAbsent = false;
                        foreach ($dates as $d) {
                            $cell = $row[$d] ?? null;
                            if ($cell) {
                                if (!empty($cell['present'])) $hasPresent = true;
                                if (empty($cell['present']) && !empty($cell['permit_type_id'])) $hasExcused = true;
                                if (empty($cell['present']) && empty($cell['permit_type_id'])) $hasAbsent = true;
                            }
                        }
                        if ($hasPresent) $presentEmployees++;
                        if ($hasExcused) $excusedEmployees++;
                        if ($hasAbsent) $absentEmployees++;
                    }
                @endphp
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Hadir: {{ $presentEmployees }}</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Izin: {{ $excusedEmployees }}</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Tidak Hadir: {{ $absentEmployees }}</span>
                </div>
            @endif
        </div>

        @if(!empty($rows))
            <div class="overflow-x-auto">
                <table class="min-w-full border border-slate-200 rounded-md">
                    <thead class="bg-slate-50 sticky top-0 z-10 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r border-slate-200">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider border-r border-slate-200">Nama</th>
                            @foreach($dates as $d)
                                <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider border-r last:border-r-0 border-slate-200">{{ \Carbon\Carbon::parse($d)->format('d-m-Y') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($rows as $row)
                            <tr class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 border-b border-r border-slate-200">{{ $row['No'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-900 border-b border-r border-slate-200">{{ $row['Nama'] }}</td>
                                @foreach($dates as $d)
                                    @php $cell = $row[$d] ?? ['count' => 0, 'present' => false, 'permit_type_id' => null, 'absent_reason' => null]; @endphp
                                    <td class="px-3 py-2 text-center text-xs border-b border-r last:border-r-0 border-slate-200">
                                        @if($mode === 'check')
                                            @if($cell['present'])
                                                <span class="inline-flex items-center justify-center w-6 h-6 text-green-600">✔</span>
                                            @elseif($cell['permit_type_id'])
                                                <span class="inline-flex items-center justify-center w-6 h-6  text-blue-700">ℹ</span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6  text-red-700">✖</span>
                                            @endif
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-800">{{ $cell['count'] }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center">
                <h3 class="text-lg font-medium text-slate-900 mb-2">Tidak ada data</h3>
                <p class="text-sm text-slate-600">Tidak ada data untuk filter yang dipilih.</p>
            </div>
        @endif
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200" aria-labelledby="chart-heading">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 id="chart-heading" class="text-lg font-semibold text-slate-900 mb-1">Grafik Ringkas</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative h-64 md:h-80">
                    <div id="presenceChartLoading" class="absolute inset-0 hidden items-center justify-center bg-white/60">
                        <span class="text-sm text-slate-600">Memuat grafik…</span>
                    </div>
                    <div id="presenceChartEmpty" class="hidden text-center text-sm text-slate-600 py-8">Tidak ada data</div>
                    <canvas id="presenceChart" class="w-full h-full" aria-label="Grafik Hadir vs Tidak Hadir"></canvas>
                </div>
                <div class="relative h-64 md:h-80">
                    <div id="absenceChartLoading" class="absolute inset-0 hidden items-center justify-center bg-white/60">
                        <span class="text-sm text-slate-600">Memuat grafik…</span>
                    </div>
                    <div id="absenceChartEmpty" class="hidden text-center text-sm text-slate-600 py-8">Tidak ada data</div>
                    <canvas id="absenceBreakdownChart" class="w-full h-full" aria-label="Grafik Rincian Ketidakhadiran"></canvas>
                </div>
            </div>
            <script type="application/json" id="matrix-totals">@json($totals)</script>
        </div>
    </section>

    @push('scripts')
        <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
        <script>
            (function() {
                var chartPresence = null;
                var chartAbsence = null;
                function parseTotals() {
                    try {
                        var el = document.getElementById('matrix-totals');
                        return JSON.parse(el ? (el.textContent || '{}') : '{}');
                    } catch (e) {
                        return null;
                    }
                }
                function showLoading(id, show) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.classList.toggle('hidden', !show);
                    if (show) {
                        el.classList.add('flex');
                    } else {
                        el.classList.remove('flex');
                    }
                }
                function toggleEmpty(id, isEmpty) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.classList.toggle('hidden', !isEmpty);
                }
                function calcMax(values) {
                    var m = 0;
                    for (var i = 0; i < values.length; i++) m = Math.max(m, values[i] || 0);
                    return m === 0 ? 5 : Math.ceil(m * 1.15);
                }
                function initCharts(totals) {
                    var presenceCtx = document.getElementById('presenceChart').getContext('2d');
                    var absenceCtx = document.getElementById('absenceBreakdownChart').getContext('2d');
                    chartPresence = new Chart(presenceCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Hadir', 'Tidak Hadir'],
                            datasets: [{
                                label: 'Jumlah Hari',
                                data: [totals.present || 0, totals.absent || 0],
                                backgroundColor: ['#22c55e', '#ef4444'],
                                borderSkipped: false,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: 8 },
                            plugins: { legend: { display: true, position: 'bottom' } },
                            scales: { y: { beginAtZero: true, suggestedMax: calcMax([totals.present || 0, totals.absent || 0]) } }
                        }
                    });
                    chartAbsence = new Chart(absenceCtx, {
                        type: 'pie',
                        data: {
                            labels: ['Berizin', 'Tanpa Keterangan'],
                            datasets: [{
                                data: [totals.absent_by_permit || 0, totals.absent_unexcused || 0],
                                backgroundColor: ['#3b82f6', '#f59e0b'],
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, layout: { padding: 8 }, plugins: { legend: { display: true, position: 'bottom' } } }
                    });
                }
                function updateCharts(totals) {
                    if (!chartPresence || !chartAbsence) return initCharts(totals);
                    var emptyPresence = (!totals || ((totals.present || 0) + (totals.absent || 0)) === 0);
                    var emptyAbsence = (!totals || ((totals.absent_by_permit || 0) + (totals.absent_unexcused || 0)) === 0);
                    toggleEmpty('presenceChartEmpty', emptyPresence);
                    toggleEmpty('absenceChartEmpty', emptyAbsence);
                    chartPresence.data.datasets[0].data = [totals.present || 0, totals.absent || 0];
                    chartPresence.options.scales.y.suggestedMax = calcMax([totals.present || 0, totals.absent || 0]);
                    chartPresence.update();
                    chartAbsence.data.datasets[0].data = [totals.absent_by_permit || 0, totals.absent_unexcused || 0];
                    chartAbsence.update();
                }
                function refresh() {
                    showLoading('presenceChartLoading', true);
                    showLoading('absenceChartLoading', true);
                    var totals = parseTotals();
                    if (!totals) {
                        toggleEmpty('presenceChartEmpty', true);
                        toggleEmpty('absenceChartEmpty', true);
                        showLoading('presenceChartLoading', false);
                        showLoading('absenceChartLoading', false);
                        return;
                    }
                    updateCharts(totals);
                    showLoading('presenceChartLoading', false);
                    showLoading('absenceChartLoading', false);
                }
                var elTotals = document.getElementById('matrix-totals');
                var initTotals = parseTotals();
                initCharts(initTotals || { present: 0, absent: 0, absent_by_permit: 0, absent_unexcused: 0 });
                refresh();
                if (elTotals) {
                    var obs = new MutationObserver(function() { refresh(); });
                    obs.observe(elTotals, { characterData: true, childList: true, subtree: true });
                }
                document.addEventListener('livewire:updated', function() { refresh(); });
                window.addEventListener('resize', function() {
                    if (chartPresence) chartPresence.resize();
                    if (chartAbsence) chartAbsence.resize();
                });
            })();
        </script>
    @endpush
</div>
