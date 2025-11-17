<x-filament-panels::page>

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
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-3 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-full">Periode Aktif</span>
                    <button id="sidebarToggle" type="button" class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300">Sembunyikan Sidebar</button>
                </div>
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 id="report-heading" class="text-lg font-semibold text-slate-900 mb-1">Matriks Kehadiran per Tanggal</h2>
                    <p class="text-sm text-slate-600">Periode: {{ \Carbon\Carbon::parse($this->start_date)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($this->end_date)->format('d-m-Y') }}</p>
                </div>
                @php
                    $queryString = http_build_query(array_filter([
                        'start_date' => $this->start_date,
                        'end_date' => $this->end_date,
                        'departemen_id' => $this->departemen_id,
                        'shift_id' => $this->shift_id,
                        'status' => $this->status,
                        'mode' => $this->mode,
                    ]));
                @endphp
                <div class="flex items-center gap-2">
                    <a id="exportExcelBtn" target="_blank" rel="noopener" href="{{ url('/api/reports/attendance-presence') }}?{{ $queryString }}&format=xlsx" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm" onclick="handleExportClick(this)">
                        <span class="fi-icon fi-icon-heroicon-o-arrow-down-tray"></span>
                        <span>Export Excel</span>
                    </a>
                    <a id="exportPdfBtn" target="_blank" rel="noopener" href="{{ url('/api/reports/attendance-presence') }}?{{ $queryString }}&format=pdf" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm" onclick="handleExportClick(this)">
                        <span class="fi-icon fi-icon-heroicon-o-arrow-down-tray"></span>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>
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
            function handleExportClick(el){
                try{
                    el.dataset.label = el.textContent;
                    el.classList.add('opacity-75');
                    el.setAttribute('aria-busy','true');
                    el.style.pointerEvents = 'none';
                    el.querySelector('span:last-child').textContent = 'Mengunduh…';
                    setTimeout(function(){
                        el.classList.remove('opacity-75');
                        el.removeAttribute('aria-busy');
                        el.style.pointerEvents = '';
                        el.querySelector('span:last-child').textContent = el.dataset.label;
                    }, 1800);
                }catch(e){}
            }
            (function() {
                var chartPresence = null;
                var chartAbsence = null;
                var refreshScheduled = false;
                function getSidebarHidden() { return localStorage.getItem('sidebarHidden') === '1'; }
                function setSidebarHidden(v) { localStorage.setItem('sidebarHidden', v ? '1' : '0'); }
                function applySidebarState(hidden) {
                    var aside = document.querySelector('aside.fi-sidebar, aside.filament-sidebar');
                    if (aside) aside.classList.toggle('hidden', hidden);
                }
                function updateSidebarToggleLabel() {
                    var btn = document.getElementById('sidebarToggle');
                    if (!btn) return;
                    btn.textContent = getSidebarHidden() ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar';
                }
                function parseTotals() {
                    try {
                        var el = document.getElementById('matrix-totals');
                        return JSON.parse(el ? (el.textContent || '{}') : '{}');
                    } catch (e) {
                        return null;
                    }
                }
                function getMetrics() {
                    var p = document.getElementById('presenceChart');
                    var w = p && p.parentElement ? (p.parentElement.clientWidth || 600) : 600;
                    var h = Math.max(240, Math.min(480, Math.round(w * 0.55)));
                    var font = w < 400 ? 11 : w < 640 ? 12 : w < 900 ? 13 : 14;
                    var padding = w < 400 ? 6 : w < 640 ? 8 : 10;
                    var radius = w < 400 ? 4 : 6;
                    return { w: w, h: h, font: font, padding: padding, radius: radius };
                }
                function setContainerHeights() {
                    var p = document.getElementById('presenceChart');
                    var a = document.getElementById('absenceBreakdownChart');
                    [p, a].forEach(function(c) {
                        if (!c || !c.parentElement) return;
                        var w = c.parentElement.clientWidth || 600;
                        var h = Math.max(240, Math.min(420, Math.round(w * 0.55)));
                        c.parentElement.style.height = h + 'px';
                    });
                }
                function syncCanvasSizes() {
                    var p = document.getElementById('presenceChart');
                    var a = document.getElementById('absenceBreakdownChart');
                    [p, a].forEach(function(c) {
                        if (!c || !c.parentElement) return;
                        var w = c.parentElement.clientWidth || 600;
                        var h = c.parentElement.clientHeight || 300;
                        c.style.width = '100%';
                        c.style.height = '100%';
                        c.width = w;
                        c.height = h;
                    });
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
                function buildPresenceLabels(t) {
                    var p = (t && t.present) ? t.present : 0;
                    var z = (t && t.absent_by_permit) ? t.absent_by_permit : 0;
                    var a = (t && t.absent_unexcused) ? t.absent_unexcused : 0;
                    return ['Hadir (' + p + ')', 'Izin (' + z + ')', 'Tidak Hadir (' + a + ')'];
                }
                function initCharts(totals) {
                    var presenceCtx = document.getElementById('presenceChart').getContext('2d');
                    var absenceCtx = document.getElementById('absenceBreakdownChart').getContext('2d');
                    var metrics = getMetrics();
                    chartPresence = new Chart(presenceCtx, {
                        type: 'doughnut',
                        data: {
                            labels: buildPresenceLabels(totals),
                            datasets: [{
                                label: 'Grafik Kehadiran',
                                data: [
                                    totals.present || 0,
                                    totals.absent_by_permit || 0,
                                    totals.absent_unexcused || 0
                                ],
                                backgroundColor: ['#22c55e', '#3b82f6', '#ef4444'],
                                borderSkipped: false,
                                borderRadius: metrics.radius
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: metrics.padding },
                            animation: { duration: 600, easing: 'easeInOutQuart' },
                            plugins: { legend: { display: true, position: 'bottom', labels: { font: { size: metrics.font } } }, tooltip: { titleFont: { size: metrics.font + 1 }, bodyFont: { size: metrics.font } } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    min: 0,
                                    suggestedMin: 0,
                                    suggestedMax: calcMax([
                                        totals.present || 0,
                                        totals.absent_by_permit || 0,
                                        totals.absent_unexcused || 0
                                    ]),
                                    ticks: { font: { size: metrics.font } }
                                },
                                x: {
                                    ticks: { autoSkip: false, font: { size: metrics.font }, maxRotation: 0 }
                                }
                            }
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
                        options: { responsive: true, maintainAspectRatio: false, layout: { padding: metrics.padding }, animation: { duration: 600, easing: 'easeInOutQuart' }, plugins: { legend: { display: true, position: 'bottom', labels: { font: { size: metrics.font } } }, tooltip: { titleFont: { size: metrics.font + 1 }, bodyFont: { size: metrics.font } } } }
                    });
                }
                function updateCharts(totals) {
                    if (!chartPresence || !chartAbsence) return initCharts(totals);
                    var metrics = getMetrics();
                    var emptyPresence = (!totals || (
                        (totals.present || 0) +
                        (totals.absent_by_permit || 0) +
                        (totals.absent_unexcused || 0)
                    ) === 0);
                    var emptyAbsence = (!totals || ((totals.absent_by_permit || 0) + (totals.absent_unexcused || 0)) === 0);
                    toggleEmpty('presenceChartEmpty', emptyPresence);
                    toggleEmpty('absenceChartEmpty', emptyAbsence);
                    chartPresence.data.labels = buildPresenceLabels(totals);
                    chartPresence.data.datasets[0].data = [
                        totals.present || 0,
                        totals.absent_by_permit || 0,
                        totals.absent_unexcused || 0
                    ];
                    chartPresence.data.datasets[0].borderRadius = metrics.radius;
                    chartPresence.options.scales.y.min = 0;
                    chartPresence.options.scales.y.suggestedMin = 0;
                    chartPresence.options.scales.y.suggestedMax = calcMax([
                        totals.present || 0,
                        totals.absent_by_permit || 0,
                        totals.absent_unexcused || 0
                    ]);
                    chartPresence.options.scales.y.ticks = chartPresence.options.scales.y.ticks || {};
                    chartPresence.options.scales.y.ticks.font = { size: metrics.font };
                    chartPresence.options.scales.x.ticks = chartPresence.options.scales.x.ticks || {};
                    chartPresence.options.scales.x.ticks.font = { size: metrics.font };
                    chartPresence.options.layout = chartPresence.options.layout || {};
                    chartPresence.options.layout.padding = metrics.padding;
                    chartPresence.options.plugins = chartPresence.options.plugins || {};
                    chartPresence.options.plugins.legend = chartPresence.options.plugins.legend || {};
                    chartPresence.options.plugins.legend.labels = chartPresence.options.plugins.legend.labels || {};
                    chartPresence.options.plugins.legend.labels.font = { size: metrics.font };
                    chartPresence.options.plugins.tooltip = chartPresence.options.plugins.tooltip || {};
                    chartPresence.options.plugins.tooltip.titleFont = { size: metrics.font + 1 };
                    chartPresence.options.plugins.tooltip.bodyFont = { size: metrics.font };
                    chartPresence.update();
                    chartAbsence.data.datasets[0].data = [totals.absent_by_permit || 0, totals.absent_unexcused || 0];
                    chartAbsence.options.layout = chartAbsence.options.layout || {};
                    chartAbsence.options.layout.padding = metrics.padding;
                    chartAbsence.options.plugins = chartAbsence.options.plugins || {};
                    chartAbsence.options.plugins.legend = chartAbsence.options.plugins.legend || {};
                    chartAbsence.options.plugins.legend.labels = chartAbsence.options.plugins.legend.labels || {};
                    chartAbsence.options.plugins.legend.labels.font = { size: metrics.font };
                    chartAbsence.options.plugins.tooltip = chartAbsence.options.plugins.tooltip || {};
                    chartAbsence.options.plugins.tooltip.titleFont = { size: metrics.font + 1 };
                    chartAbsence.options.plugins.tooltip.bodyFont = { size: metrics.font };
                    chartAbsence.update();
                }
                function doRefresh() {
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
                function refresh() {
                    if (refreshScheduled) return;
                    refreshScheduled = true;
                    setTimeout(function() {
                        setContainerHeights();
                        syncCanvasSizes();
                        doRefresh();
                        refreshScheduled = false;
                    }, 120);
                }
                var elTotals = document.getElementById('matrix-totals');
                var initTotals = parseTotals();
                setContainerHeights();
                applySidebarState(getSidebarHidden());
                updateSidebarToggleLabel();
                initCharts(initTotals || { present: 0, absent: 0, absent_by_permit: 0, absent_unexcused: 0 });
                refresh();
                var btn = document.getElementById('sidebarToggle');
                if (btn) {
                    btn.addEventListener('click', function() {
                        var next = !getSidebarHidden();
                        setSidebarHidden(next);
                        applySidebarState(next);
                        setContainerHeights();
                        syncCanvasSizes();
                        refresh();
                        updateSidebarToggleLabel();
                    });
                }
                if (elTotals) {
                    var obs = new MutationObserver(function() { refresh(); });
                    obs.observe(elTotals, { characterData: true, childList: true, subtree: true });
                }
                document.addEventListener('livewire:updated', function() { refresh(); });
                window.addEventListener('resize', function() {
                    setContainerHeights();
                    syncCanvasSizes();
                    if (chartPresence) chartPresence.resize();
                    if (chartAbsence) chartAbsence.resize();
                });
            })();
        </script>
    @endpush
</div>
</x-filament-panels::page>

