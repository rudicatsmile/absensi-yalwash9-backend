# Matrix Attendance Report

## Overview
Fitur Matrix Attendance Report menampilkan kehadiran karyawan dalam bentuk tabel matriks dengan kolom tanggal dinamis. Setiap sel tanggal berisi status kehadiran:
- `O`: Hadir (on_time)
- `L`: Terlambat (late)
- `A`: Absen (absent)
- `X`: Hari Minggu
- `-`: Tidak ada data

Tabel juga menyertakan ringkasan per karyawan: `Total_kehadiran`, `Total_tidak_hadir`, `Total_jam_kerja`.

## Cara Akses
Buka halaman Filament: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix`.

## Parameter Tersedia
- `start_date`: tanggal mulai periode (`YYYY-MM-DD`)
- `end_date`: tanggal akhir periode (`YYYY-MM-DD`)
- `shift_id`: filter shift (ID dari `shift_kerjas`)
- `search`: pencarian nama karyawan (LIKE)
- `per_page`: jumlah baris per halaman (default 25)
- `page`: nomor halaman (default 1)

## Contoh URL
- Tanpa filter: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix`
- Periode tertentu: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix&start_date=2025-11-01&end_date=2025-11-30`
- Filter shift: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix&shift_id=1`
- Pencarian: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix&search=Budi`
- Pagination: `http://127.0.0.1:8000/admin/attendance-report?report_mode=matrix&per_page=10&page=2`

## Tampilan yang Diharapkan
- Tabel dengan kolom: `No`, `Nama`, kolom tanggal berformat `DD-MM-YYYY`, lalu `Total Hadir`, `Total Tidak Hadir`, `Total Jam Kerja`.
- Badge warna untuk status: Hijau (O), Kuning (L), Merah (A), Biru (X), Abu (–).
- Desain selaras dengan gaya tabel yang sudah ada (border Tailwind, sticky header, scroll horizontal).

## Catatan
- Mode `matrix` akan fallback ke `detail` jika data tidak valid.
- Tombol “Generate Matrix” tersedia di header actions halaman untuk memudahkan akses.
- Query backend mereplikasi `query-absensi.sql` dengan SQL dinamis via `getMatrixData()` di `AttendanceReport.php`