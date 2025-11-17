<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran</title>
    <style>
        body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#0f172a}
        .title{font-size:18px; font-weight:700; margin-bottom:4px}
        .subtitle{font-size:12px; color:#334155; margin-bottom:12px}
        table{width:100%; border-collapse:collapse}
        th,td{border:1px solid #e2e8f0; padding:6px 8px; text-align:center}
        th{background:#f1f5f9; font-weight:600; font-size:11px}
        td.text-left{ text-align:left }
    </style>
</head>
<body>
    @php
        $dates = $matrix['dates'] ?? [];
        $rows = $matrix['rows'] ?? [];
        $mode = $matrix['mode'] ?? 'check';
    @endphp
    <div class="title">Laporan Kehadiran & Tidak Hadir</div>
    <div class="subtitle">Periode: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['end_date'])->format('d-m-Y') }} • Dibuat: {{ $exported_at }}</div>
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
</body>
</html>