<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'shift_id' => ['nullable', 'integer', 'exists:shift_kerjas,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'export' => ['nullable', 'in:csv,xlsx'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $shiftId = $request->input('shift_id');
        $departemenId = $request->input('departemen_id');

        $page = (int) ($request->input('page', 1));
        $perPage = (int) ($request->input('per_page', 250));
        $offset = ($page - 1) * $perPage;

        // Set variabel MySQL untuk dipakai di query dinamis (berdasarkan query.sql)
        DB::statement('SET @start_date = ?', [$startDate]);
        DB::statement('SET @end_date = ?', [$endDate]);
        if ($shiftId !== null) {
            DB::statement('SET @shift_id = ?', [(int) $shiftId]);
        } else {
            DB::statement('SET @shift_id = NULL');
        }
        //add @departemen_id
        if ($departemenId !== null) {
            DB::statement('SET @departemen_id = ?', [(int) $departemenId]);
        } else {
            DB::statement('SET @departemen_id = NULL');
        }
        DB::statement('SET @limit = ?', [$perPage]);
        DB::statement('SET @offset = ?', [$offset]);

        // 2. Generate kolom tanggal dinamis
        DB::statement("
            SELECT GROUP_CONCAT(DISTINCT
              CONCAT(
                'MAX(CASE WHEN a.date = ''', d.date, ''' THEN ',
                'CASE ',
                  'WHEN DAYOFWEEK(''', d.date, ''') = 1 THEN ''X'' ',
                  'WHEN a.status = ''on_time'' THEN ''O'' ',
                  'WHEN a.status = ''late'' THEN ''L'' ',
                  'WHEN a.status = ''absent'' THEN ''A'' ',
                  'ELSE ''-'' ',
                'END ELSE ',
                'CASE WHEN DAYOFWEEK(''', d.date, ''') = 1 THEN ''X'' ELSE ''-'' END ',
                'END) AS `', DATE_FORMAT(d.date, '%d-%m-%Y'), '`'
              )
              SEPARATOR ',\\n    '
            ) INTO @columns
            FROM (
              SELECT DATE_ADD(@start_date, INTERVAL (ones.n + tens.n*10 + hundreds.n*100) DAY) AS DATE
              FROM
                (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) ones
              CROSS JOIN
                (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens
              CROSS JOIN
                (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) hundreds
              WHERE DATE_ADD(@start_date, INTERVAL (ones.n + tens.n*10 + hundreds.n*100) DAY) <= @end_date
            ) d;
        ");

        // 3. Total jam kerja
        DB::statement("
            SET @total_jam_kerja = '
              ROUND(SUM(
                CASE
                  WHEN a.time_out IS NOT NULL AND a.time_in IS NOT NULL
                  THEN TIME_TO_SEC(TIMEDIFF(a.time_out, a.time_in)) / 3600.0
                  ELSE 0
                END
              ), 2)
            ';
        ");

        // 4. Total tidak hadir
        DB::statement("
            SET @total_tidak_hadir = '
              SUM(
                CASE
                  WHEN a.status IS NULL OR a.status = ''absent'' THEN 1
                  ELSE 0
                END
              )
            ';
        ");

        // 5. Bangun query utama + pagination
        DB::statement("
            SET @sql = CONCAT(
              'SELECT ',
                'ROW_NUMBER() OVER (ORDER BY u.id) AS No, ',
                'u.name AS Nama_Pegawai, ',
                @columns, ',\\n    ',
                'SUM(CASE WHEN a.status IN (''on_time'', ''late'') THEN 1 ELSE 0 END) AS Total_kehadiran, ',
                @total_tidak_hadir, ' AS Total_tidak_hadir, ',
                @total_jam_kerja, ' AS Total_jam_kerja\\n',
              'FROM users u ',
              'LEFT JOIN attendances a ON u.id = a.user_id AND a.date BETWEEN ''', @start_date, ''' AND ''', @end_date, '''',
              IF(@shift_id IS NOT NULL,
                 CONCAT('  AND a.shift_id = ', @shift_id, '\\n'),
                 '  -- Semua shift\\n'
              ),
              'WHERE 1=1 ',
              -- add @departemen_id
              IF(@departemen_id IS NOT NULL,
                 CONCAT('  AND u.departemen_id = ', @departemen_id, '\\n'),
                 '  -- Semua departemen\\n'
              ),
              'GROUP BY u.id, u.name\\n',
              'ORDER BY u.id\\n',
              'LIMIT ', @limit, ' OFFSET ', @offset
            );
        ");

        //write to log @sql
        // Log::info('AttendanceReportQuery', ['sql' => @sql]);


        DB::statement('PREPARE stmt FROM @sql;');
        $rows = DB::select('EXECUTE stmt;');
        DB::statement('DEALLOCATE PREPARE stmt;');

        // Ambil deskripsi shift (metadata)
        $shiftMeta = null;
        if ($shiftId !== null) {
            $shiftMeta = DB::table('shift_kerjas')
                ->select('id', 'name', 'start_time', 'end_time')
                ->where('id', (int) $shiftId)
                ->first();
        }

        // Total baris (untuk pagination) -> jumlah user, karena LEFT JOIN tidak memfilter user
        $total = DB::table('users')->count();

        // Export
        $export = $request->input('export');
        if ($export === 'csv' || $export === 'xlsx') {
            $filename = sprintf(
                'attendance_report_%s_%s%s.%s',
                $startDate,
                $endDate,
                $shiftId ? ('_shift_' . $shiftId) : '',
                $export === 'xlsx' ? 'xlsx' : 'csv'
            );

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');

                // Header dari keys baris pertama
                if (!empty($rows)) {
                    fputcsv($out, array_keys((array) $rows[0]));
                }

                foreach ($rows as $row) {
                    fputcsv($out, (array) $row);
                }

                fclose($out);
            }, $filename, $headers);
        }

        // Response JSON
        return response()->json([
            'message' => 'Report generated successfully',
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'shift_id' => $shiftId,
            ],
            'shift' => $shiftMeta,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / max($perPage, 1)),
            ],
            'data' => $rows,
        ], 200);
    }

    public function attendancePresenceMatrix(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shift_kerjas,id'],
            'status' => ['nullable', 'in:semua,Hadir,Tidak Hadir'],
            'mode' => ['nullable', 'in:check,jumlah shift'],
            'format' => ['nullable', 'in:pdf,xlsx']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemenId = $request->input('departemen_id');
        $shiftId = $request->input('shift_id');
        $status = $request->input('status', 'semua');
        $mode = $request->input('mode', 'check');

        $service = app(\App\Services\Reports\AttendancePresenceService::class);
        $result = $service->buildMatrix($startDate, $endDate, $departemenId, $shiftId, $status, $mode);

        $format = strtolower($request->query('format', 'json'));

        if ($format === 'pdf') {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.attendance-presence-report-pdf', [
                    'matrix' => $result,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'departemen_id' => $departemenId,
                        'shift_id' => $shiftId,
                        'status' => $status,
                        'mode' => $mode,
                    ],
                    'exported_at' => now()->format('d/m/Y H:i'),
                ])->setPaper('A4', 'landscape')->setOptions([
                            'dpi' => 150,
                            'defaultFont' => 'sans-serif',
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                        ]);

                $filename = 'laporan-kehadiran-' . now()->format('Y-m-d-H-i-s') . '.pdf';
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename, ['Content-Type' => 'application/pdf']);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Export PDF gagal', 'error' => $e->getMessage()], 500);
            }
        }

        if ($format === 'xlsx') {
            try {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue('A1', 'No');
                $sheet->setCellValue('B1', 'Nama');
                $col = 3;
                foreach ($result['dates'] as $d) {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', \Carbon\Carbon::parse($d)->format('d-m-Y'));
                    $col++;
                }

                $rowIndex = 2;
                foreach ($result['rows'] as $r) {
                    $sheet->setCellValue('A' . $rowIndex, $r['No']);
                    $sheet->setCellValue('B' . $rowIndex, $r['Nama']);
                    $col = 3;
                    foreach ($result['dates'] as $d) {
                        $cell = $r[$d] ?? ['count' => 0, 'present' => false, 'permit_type_id' => null];
                        $val = $result['mode'] === 'check'
                            ? ($cell['present'] ? '✔' : ($cell['permit_type_id'] ? 'ℹ' : '✖'))
                            : ($cell['count'] ?? 0);
                        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $rowIndex, $val);
                        $col++;
                    }
                    $rowIndex++;
                }

                foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($result['dates']) + 2)) as $colLetter) {
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
                $sheet->freezePane('C2');
                $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($result['dates']) + 2) . '1')->getFont()->setBold(true);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'laporan-kehadiran-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                return response()->streamDownload(function () use ($writer) {
                    $writer->save('php://output');
                }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Export Excel gagal', 'error' => $e->getMessage()], 500);
            }
        }

        return response()->json([
            'message' => 'OK',
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'departemen_id' => $departemenId,
                'shift_id' => $shiftId,
                'status' => $status,
                'mode' => $mode,
            ],
            'data' => $result,
        ], 200);
    }

    public function presentEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shift_kerjas,id'],
            'q' => ['nullable', 'string'],
            'sort' => ['nullable', 'in:name,departemen_name,total_hadir'],
            'dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $departemenId = $request->input('departemen_id');
        $shiftId = $request->input('shift_id');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'name');
        $dir = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $base = DB::table('users')
            ->join('attendances', 'users.id', '=', 'attendances.user_id')
            ->leftJoin('departemens', 'users.departemen_id', '=', 'departemens.id')
            ->whereBetween('attendances.date', [$start, $end])
            ->when($departemenId, fn($q2) => $q2->where('users.departemen_id', (int) $departemenId))
            ->when($shiftId, fn($q2) => $q2->where('attendances.shift_id', (int) $shiftId))
            ->when($q !== '', fn($q2) => $q2->where('users.name', 'like', '%' . $q . '%'));

        $total = (clone $base)->selectRaw('COUNT(DISTINCT users.id) as aggregate')->value('aggregate') ?? 0;

        $rows = (clone $base)
            ->select([
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT attendances.date) as total_hadir'),
                DB::raw('COALESCE(departemens.name, "-") as departemen_name'),
            ])
            ->groupBy('users.id', 'users.name', 'users.departemen_id', 'departemens.name')
            ->orderBy(match ($sort) {
                'departemen_name' => DB::raw('COALESCE(departemens.name, "-")'),
                'total_hadir' => DB::raw('total_hadir'),
                default => 'users.name',
            }, $dir)
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->get();

        return response()->json([
            'message' => 'OK',
            'filters' => [
                'start_date' => $start,
                'end_date' => $end,
                'departemen_id' => $departemenId,
                'shift_id' => $shiftId,
            ],
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil(($total ?: 0) / max(1, $perPage)),
            ],
            'data' => $rows,
        ], 200);
    }

    public function permitEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shift_kerjas,id'],
            'q' => ['nullable', 'string'],
            'sort' => ['nullable', 'in:name,departemen_name,jabatan_name,total_izin'],
            'dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $departemenId = $request->input('departemen_id');
        $shiftId = $request->input('shift_id');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'name');
        $dir = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $users = DB::table('users')
            ->leftJoin('departemens', 'users.departemen_id', '=', 'departemens.id')
            ->leftJoin('jabatans', 'users.jabatan_id', '=', 'jabatans.id')
            ->select([
                'users.id',
                'users.name',
                DB::raw('COALESCE(departemens.name, "-") as departemen_name'),
                DB::raw('COALESCE(jabatans.name, "-") as jabatan_name'),
            ])
            ->when($departemenId, fn($q2) => $q2->where('users.departemen_id', (int) $departemenId))
            ->when($q !== '', fn($q2) => $q2->where('users.name', 'like', '%' . $q . '%'))
            ->get();

        $permits = DB::table('permits')
            ->select(['employee_id', 'start_date', 'end_date', 'status'])
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $start)
            ->whereDate('start_date', '<=', $end)
            ->get();

        $permitDays = [];
        foreach ($permits as $p) {
            $ps = $p->start_date;
            $pe = $p->end_date;
            $cur2 = $ps < $start ? $start : $ps;
            $end2 = $pe > $end ? $end : $pe;
            while ($cur2 <= $end2) {
                $permitDays[$p->employee_id][$cur2] = true;
                $cur2 = date('Y-m-d', strtotime($cur2 . ' +1 day'));
            }
        }

        $rows = [];
        foreach ($users as $u) {
            $count = isset($permitDays[$u->id]) ? count($permitDays[$u->id]) : 0;
            if ($count > 0) {
                $rows[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'departemen_name' => $u->departemen_name,
                    'jabatan_name' => $u->jabatan_name,
                    'total_izin' => $count,
                ];
            }
        }

        if (!empty($rows)) {
            usort($rows, function ($a, $b) use ($sort, $dir) {
                $av = $a[$sort] ?? null;
                $bv = $b[$sort] ?? null;
                if ($sort === 'total_izin') {
                    $cmp = ($av <=> $bv);
                } else {
                    $cmp = strcasecmp((string) $av, (string) $bv);
                }
                return $dir === 'desc' ? -$cmp : $cmp;
            });
        }

        $total = count($rows);
        $offset = max(0, ($page - 1) * $perPage);
        $paged = array_slice($rows, $offset, $perPage);

        return response()->json([
            'message' => 'OK',
            'filters' => [
                'start_date' => $start,
                'end_date' => $end,
                'departemen_id' => $departemenId,
                'shift_id' => $shiftId,
            ],
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil(($total ?: 0) / max(1, $perPage)),
            ],
            'data' => $paged,
        ], 200);
    }

    public function absentEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shift_kerjas,id'],
            'q' => ['nullable', 'string'],
            'sort' => ['nullable', 'in:name,departemen_name,jabatan_name,total_alpa'],
            'dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $departemenId = $request->input('departemen_id');
        $shiftId = $request->input('shift_id');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'name');
        $dir = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $dates = [];
        $cur = $start;
        while ($cur <= $end) {
            $dates[] = $cur;
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }

        $users = DB::table('users')
            ->leftJoin('departemens', 'users.departemen_id', '=', 'departemens.id')
            ->leftJoin('jabatans', 'users.jabatan_id', '=', 'jabatans.id')
            ->select([
                'users.id',
                'users.name',
                DB::raw('COALESCE(departemens.name, "-") as departemen_name'),
                DB::raw('COALESCE(jabatans.name, "-") as jabatan_name'),
            ])
            ->when($departemenId, fn($q2) => $q2->where('users.departemen_id', (int) $departemenId))
            ->when($q !== '', fn($q2) => $q2->where('users.name', 'like', '%' . $q . '%'))
            ->get();

        $attRaw = DB::table('attendances')
            ->select(['user_id', 'date'])
            ->whereBetween('date', [$start, $end])
            ->when($shiftId, fn($q2) => $q2->where('shift_id', (int) $shiftId))
            ->get();

        $attIndex = [];
        foreach ($attRaw as $a) {
            $attIndex[$a->user_id . '|' . $a->date] = true;
        }

        $permits = DB::table('permits')
            ->select(['employee_id', 'start_date', 'end_date', 'status'])
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $start)
            ->whereDate('start_date', '<=', $end)
            ->get();

        $permitIndex = [];
        foreach ($permits as $p) {
            $ps = $p->start_date;
            $pe = $p->end_date;
            $cur2 = $ps < $start ? $start : $ps;
            $end2 = $pe > $end ? $end : $pe;
            while ($cur2 <= $end2) {
                $permitIndex[$p->employee_id . '|' . $cur2] = true;
                $cur2 = date('Y-m-d', strtotime($cur2 . ' +1 day'));
            }
        }

        $rows = [];
        foreach ($users as $u) {
            $alpa = 0;
            foreach ($dates as $d) {
                $key = $u->id . '|' . $d;
                $present = isset($attIndex[$key]);
                $permitted = isset($permitIndex[$key]);
                if (!$present && !$permitted) {
                    $alpa++;
                }
            }
            if ($alpa > 0) {
                $rows[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'departemen_name' => $u->departemen_name,
                    'jabatan_name' => $u->jabatan_name,
                    'reason' => 'Alpa',
                    'total_alpa' => $alpa,
                ];
            }
        }

        if (!empty($rows)) {
            usort($rows, function ($a, $b) use ($sort, $dir) {
                $av = $a[$sort] ?? null;
                $bv = $b[$sort] ?? null;
                if ($sort === 'total_alpa') {
                    $cmp = ($av <=> $bv);
                } else {
                    $cmp = strcasecmp((string) $av, (string) $bv);
                }
                return $dir === 'desc' ? -$cmp : $cmp;
            });
        }

        $total = count($rows);
        $offset = max(0, ($page - 1) * $perPage);
        $paged = array_slice($rows, $offset, $perPage);

        return response()->json([
            'message' => 'OK',
            'filters' => [
                'start_date' => $start,
                'end_date' => $end,
                'departemen_id' => $departemenId,
                'shift_id' => $shiftId,
            ],
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil(($total ?: 0) / max(1, $perPage)),
            ],
            'data' => $paged,
        ], 200);
    }
}
