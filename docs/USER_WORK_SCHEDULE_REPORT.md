# Laporan Jadwal Kerja Pegawai

Fitur ini memungkinkan admin untuk melihat dan mengunduh laporan jadwal kerja pegawai secara interaktif. Laporan mencakup informasi tanggal, nama pegawai, departemen, shift, jam masuk, jam pulang, dan total jam kerja.

## Cara Mengakses
1. Login sebagai Admin, Kepala Lembaga, atau Manager.
2. Navigasi ke menu **Users** (`/admin/users`).
3. Klik tombol **Laporan Jadwal** (ikon dokumen/chart) yang terletak di bagian header tabel (sebelah tombol New User).

## Fitur Utama

### 1. Filter Data
Laporan ini menyediakan beberapa filter untuk memudahkan pencarian data:
- **Tanggal Mulai & Tanggal Selesai**: Memfilter data berdasarkan rentang tanggal tertentu. Secara default menampilkan data bulan berjalan.
- **Departemen**: Memfilter data berdasarkan departemen pegawai.
- **Pencarian**: Kotak pencarian untuk mencari berdasarkan nama pegawai.

### 2. Tampilan Tabel Interaktif
- Tabel menampilkan data secara paginasi (10 baris per halaman).
- Data dimuat secara lazy (lazy loading) untuk performa yang lebih baik.
- Tampilan responsif dan menyesuaikan dengan ukuran layar (mobile-friendly).

### 3. Export Data
Tersedia dua opsi untuk mengunduh laporan:
- **Export Excel**: Mengunduh data dalam format `.xlsx`.
- **Export PDF**: Mengunduh data dalam format `.pdf`.

Data yang diexport akan mengikuti filter yang sedang diterapkan pada tabel.

## Detail Teknis

### Komponen Livewire
Fitur ini dibangun menggunakan Livewire component `App\Livewire\UserWorkScheduleReport`.
- **File Class**: `app/Livewire/UserWorkScheduleReport.php`
- **File View**: `resources/views/livewire/user-work-schedule-report.blade.php`

### Integrasi Filament
Komponen ini diintegrasikan ke dalam halaman `ListUsers` Filament menggunakan `Action` modal.
- **File Resource**: `app/Filament/Resources/Users/Pages/ListUsers.php`
- **Modal View**: `resources/views/filament/pages/report-modal.blade.php`

### Model Terkait
- `EmployeeWorkTimeSchedule`: Sumber data utama jadwal kerja.
- `User`: Data pegawai.
- `Departemen`: Data departemen.
- `ShiftKerja`: Data shift.
- `JamKerja`: Data jam kerja master.

### Dependensi
- `maatwebsite/excel` (PhpSpreadsheet): Untuk export Excel.
- `barryvdh/laravel-dompdf`: Untuk export PDF.

## Testing
Unit test untuk fitur ini dapat dijalankan dengan perintah:
```bash
php artisan test tests/Feature/Livewire/UserWorkScheduleReportTest.php
```
Coverage test mencakup rendering komponen, filtering, dan fungsi export.
