-- Absensi selama 1 bulan dengan pilihan waktu dan shift_id. Hari minggu di beri tanda 'X'
SET @start_date = '2025-11-01';
SET @end_date = '2025-11-31';
SET @shift_id = 1;  -- Ganti dengan shift yang diinginkan, atau NULL untuk semua

-- === 2. Generate kolom tanggal dinamis (dengan 'X' untuk hari Minggu) ===
SELECT GROUP_CONCAT(DISTINCT
  CONCAT(
    'MAX(CASE WHEN a.date = ''', d.date, ''' THEN ',
    'CASE ',
      'WHEN DAYOFWEEK(''', d.date, ''') = 1 THEN ''X'' ',  -- Minggu = 1
      'WHEN a.status = ''on_time'' THEN ''O'' ',
      'WHEN a.status = ''late'' THEN ''L'' ',
      'WHEN a.status = ''absent'' THEN ''A'' ',
      'ELSE ''-'' ',
    'END ELSE ',
    'CASE WHEN DAYOFWEEK(''', d.date, ''') = 1 THEN ''X'' ELSE ''-'' END ',
    'END) AS `', DATE_FORMAT(d.date, '%d-%m-%Y'), '`'
  )
  SEPARATOR ',\n    '
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

-- === 3. Hitung Total Jam Kerja (dalam jam desimal) ===
SET @total_jam_kerja = '
  ROUND(SUM(
    CASE
      WHEN a.time_out IS NOT NULL AND a.time_in IS NOT NULL
      THEN TIME_TO_SEC(TIMEDIFF(a.time_out, a.time_in)) / 3600.0
      ELSE 0
    END
  ), 2)
';

-- === 4. Hitung Total Tidak Hadir (hari tanpa record atau status absent) ===
SET @total_tidak_hadir = '
  SUM(
    CASE
      WHEN a.status IS NULL OR a.status = ''absent'' THEN 1
      ELSE 0
    END
  )
';

-- === 5. Bangun query utama ===
SET @sql = CONCAT(
  'SELECT ',
    'ROW_NUMBER() OVER (ORDER BY u.id) AS No, ',
    'u.name AS Nama_Pegawai, ',
    @columns, ',\n    ',
    'SUM(CASE WHEN a.status IN (''on_time'', ''late'') THEN 1 ELSE 0 END) AS Total_kehadiran, ',
    @total_tidak_hadir, ' AS Total_tidak_hadir, ',
    @total_jam_kerja, ' AS Total_jam_kerja\n',
  'FROM users u ',
  'LEFT JOIN attendances a ON u.id = a.user_id AND a.date BETWEEN ''', @start_date, ''' AND ''', @end_date, '''\n',
  -- 'WHERE u.role = ''user''\n',
  IF(@shift_id IS NOT NULL,
     CONCAT('  AND shift_id = ', @shift_id, '\n'),
     '  -- Semua shift\n'
  ),
  'GROUP BY u.id, u.name\n',
  'ORDER BY u.id'
);

-- === 6. Eksekusi ===
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
