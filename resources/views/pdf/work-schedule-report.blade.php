<!DOCTYPE html>
<html>
<head>
    <title>{{ $report_title }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; font-weight: bold; margin: 0 0 10px 0; }
        .info { margin-bottom: 15px; }
        .info p { margin: 2px 0; }

        .group-header {
            background-color: #e5e7eb;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 15px;
            border-bottom: 2px solid #9ca3af;
        }

        .sub-group-header {
            background-color: #f3f4f6;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 10pt;
            margin-top: 10px;
            border-left: 4px solid #3b82f6;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 15px; }
        th { background-color: #f9fafb; border: 0.5pt solid #d1d5db; padding: 8px; text-align: left; font-weight: bold; font-size: 9pt; }
        td { border: 0.5pt solid #d1d5db; padding: 8px; font-size: 9pt; }
        tr:nth-child(even) { background-color: #f9fafb; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; color: #6b7280; text-align: right; border-top: 1px solid #e5e7eb; padding-top: 5px; }
        .no-data { text-align: center; padding: 20px; font-style: italic; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report_title }}</h1>
    </div>

    <div class="info">
        <p><strong>Nama Pegawai :</strong> {{ $employee_name }}</p>
        <p><strong>Departemen :</strong> {{ $department_name }}</p>
        <p><strong>Periode :</strong> {{ $start_date }} s/d {{ $end_date }}</p>
    </div>

    @if(count($groupedData) > 0)
        @foreach($groupedData as $shiftName => $dateGroups)
            <!-- Level 1: Shift Group -->
            <div class="group-header">Shift: {{ $shiftName }}</div>

            @foreach($dateGroups as $date => $rows)
                <!-- Level 2: Date Sub-Group -->
                <div class="sub-group-header">Tanggal: {{ $date }}</div>

                <table>
                    <thead>
                        <tr>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Total Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row->start_time ? \Carbon\Carbon::parse($row->start_time)->format('H:i') : '-' }}</td>
                                <td>{{ $row->end_time ? \Carbon\Carbon::parse($row->end_time)->format('H:i') : '-' }}</td>
                                <td>
                                    @if($row->start_time && $row->end_time)
                                        @php
                                            $start = \Carbon\Carbon::parse($row->start_time);
                                            $end = \Carbon\Carbon::parse($row->end_time);
                                            $diff = $start->diffInMinutes($end);
                                            $hours = floor($diff / 60);
                                            $minutes = $diff % 60;
                                        @endphp
                                        {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endforeach
    @else
        <div class="no-data">
            Tidak ada jadwal pada periode yang dipilih
        </div>
    @endif

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</body>
</html>
