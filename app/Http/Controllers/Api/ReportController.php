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
}
