<!DOCTYPE html>
<html>
<head>
    <title>Laporan Jadwal Kerja</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; font-weight: bold; margin: 0 0 10px 0; }
        .info { margin-bottom: 15px; }
        .info p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f3f4f6; border: 0.5pt solid #d1d5db; padding: 8px; text-align: left; font-weight: bold; font-size: 9pt; }
        td { border: 0.5pt solid #d1d5db; padding: 8px; font-size: 9pt; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; color: #6b7280; text-align: right; border-top: 1px solid #e5e7eb; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Jadwal Kerja</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nama Pegawai</th>
                <th>Departemen</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }}</td>
                    <td>{{ $schedule->user->name ?? '-' }}</td>
                    <td>{{ $schedule->user->departemen->name ?? '-' }}</td>
                    <td>{{ $schedule->shift->name ?? '-' }}</td>
                    <td>{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}</td>
                    <td>{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada data jadwal pada periode ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</body>
</html>
