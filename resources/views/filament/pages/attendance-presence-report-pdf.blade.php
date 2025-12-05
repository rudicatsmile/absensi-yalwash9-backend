<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran</title>
    <style>
        @page { margin: 80px 40px 60px 40px; }
        body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#0f172a}
        header { position: fixed; top: -60px; left: 0; right: 0; height: 50px; }
        footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; font-size: 10px; color:#64748b }
        .title{font-size:18px; font-weight:700; margin-bottom:2px}
        .subtitle{font-size:12px; color:#334155}
        table{width:100%; border-collapse:collapse}
        th,td{border:1px solid #e2e8f0; padding:6px 8px; text-align:center}
        th{background:#f1f5f9; font-weight:600; font-size:11px}
        td.text-left{ text-align:left }
        .footer-right{ float:right }
        .pagenum:before{ content: counter(page); }
    </style>
</head>
<body>
    @php
        $dates = $matrix['dates'] ?? [];
        $rows = $matrix['rows'] ?? [];
        $mode = $matrix['mode'] ?? 'check';
    @endphp
    <header>
        <div class="title">Laporan Kehadiran & Tidak Hadir</div>
        <div class="subtitle">Periode: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['end_date'])->format('d-m-Y') }} • Dibuat: {{ $exported_at }}</div>
    </header>
    <table>
        <thead>
            <tr>
                <th style="text-align:left">No</th>
                <th style="text-align:left">Nama</th>
                @foreach($dates as $d)
                    <th>{{ \Carbon\Carbon::parse($d)->format('d-m-Y') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="text-left">{{ $row['No'] }}</td>
                    <td class="text-left">{{ $row['Nama'] }}</td>
                    @foreach($dates as $d)
                        @php $cell = $row[$d] ?? ['count' => 0, 'present' => false, 'permit_type_id' => null]; @endphp
                        <td>
                            @if($mode === 'check')
                                {{ $cell['present'] ? '✔' : ($cell['permit_type_id'] ? 'ℹ' : '✖') }}
                            @else
                                {{ $cell['count'] ?? 0 }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <footer>
        <span>Al-Wathoniyah Ashodriyah 9 • Sistem Absensi</span>
        <span class="footer-right">Halaman <span class="pagenum"></span></span>
    </footer>
</body>
</html>
