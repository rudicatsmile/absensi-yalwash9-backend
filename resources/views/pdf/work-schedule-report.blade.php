<!DOCTYPE html>
<html>
<head>
    <title>Laporan Jadwal Kerja</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #2c3e50; margin: 0; padding: 20px; background-color: #ffffff;">

    <!-- Main Container -->
    <div style="padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #e9ecef;">

        <!-- Header Section -->
        <div style="background: #f8f9fa; background: linear-gradient(to bottom, #f8f9fa, #e9ecef); padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #dee2e6;">
            <h2 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 24px; font-weight: 600;">Laporan Jadwal Kerja</h2>
            <p style="margin: 0; color: #34495e; font-size: 14px;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
            <p style="margin: 5px 0 0 0; color: #34495e; font-size: 12px;">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <!-- Table -->
        <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; font-size: 12px;">
            <thead>
                <tr>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-top-left-radius: 6px; border-bottom: 2px solid #1a252f;">Tanggal</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-bottom: 2px solid #1a252f;">Nama Pegawai</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-bottom: 2px solid #1a252f;">Departemen</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-bottom: 2px solid #1a252f;">Shift</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-bottom: 2px solid #1a252f;">Jam Masuk</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-bottom: 2px solid #1a252f;">Jam Pulang</th>
                    <th style="background-color: #2c3e50; color: #ffffff; padding: 12px; text-align: left; border-top-right-radius: 6px; border-bottom: 2px solid #1a252f;">Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $index => $schedule)
                    <tr style="background-color: {{ $index % 2 == 0 ? '#ffffff' : '#f8f9fa' }};">
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #2c3e50;">{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #2c3e50; font-weight: bold;">{{ $schedule->user->name ?? '-' }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #34495e;">{{ $schedule->user->departemen->name ?? '-' }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #34495e;">{{ $schedule->shift->name ?? '-' }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #2c3e50;">{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #2c3e50;">{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '-' }}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e9ecef; color: #2c3e50; font-weight: bold;">
                            @php
                                $total = '-';
                                if ($schedule->start_time && $schedule->end_time) {
                                    $start = \Carbon\Carbon::parse($schedule->start_time);
                                    $end = \Carbon\Carbon::parse($schedule->end_time);
                                    $diff = $start->diffInMinutes($end);
                                    $hours = floor($diff / 60);
                                    $minutes = $diff % 60;
                                    $total = sprintf('%02d:%02d', $hours, $minutes);
                                }
                            @endphp
                            {{ $total }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d; border-bottom: 1px solid #e9ecef;">Tidak ada data ditemukan untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div style="margin-top: 30px; border-top: 2px solid #e9ecef; padding-top: 10px; text-align: right;">
            <p style="margin: 0; color: #95a5a6; font-size: 10px;">&copy; {{ date('Y') }} Smart Attendance</p>
        </div>
    </div>
</body>
</html>
