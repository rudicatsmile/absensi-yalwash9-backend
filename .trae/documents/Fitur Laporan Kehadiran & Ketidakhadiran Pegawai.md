## Ringkasan Fitur
- Halaman laporan untuk memantau kehadiran dan ketidakhadiran pegawai dengan filter lengkap dan grafik pendukung.
- Tabel berbentuk matriks: baris = pegawai, kolom = tanggal dalam rentang yang dipilih.
- Sel berisi indikator kehadiran (ikon check/“-”) atau angka jumlah kehadiran per tanggal.
- Mendukung status ketidakhadiran karena izin (mis. sakit) dan tanpa keterangan.

## Integrasi dengan Stack Saat Ini
- Admin menggunakan Filament v4 + Livewire v3 untuk halaman dan widget.
- Grafik menggunakan Chart.js yang sudah dipakai proyek (lihat `resources/views/pages/dashboard.blade.php` dan `app/Filament/Widgets/AttendanceChartWidget.php`).
- Sidebar Blade: `resources/views/components/sidebar.blade.php` (dipakai oleh `resources/views/layouts/app.blade.php`).

## UI/UX Halaman Laporan
- Kontrol Form Utama:
  1) Dropdown status: `semua`, `Hadir`, `Tidak Hadir`.
  2) Toggle tampilan sel: `check` vs `jumlah shift`.
  3) Filter: `tanggal mulai`, `tanggal akhir`, `departemen` (relasi `users.departemen_id = departemens.id`), `shift kerja` (relasi `attendances.shift_id = shift_kerjas.id`).
- Tabel Matriks:
  - Baris menampilkan `users.name`.
  - Kolom tanggal dibuat dinamis berdasarkan rentang dipilih; setiap kolom mewakili `attendances.date`.
  - Jika tidak ada `attendances` pada tanggal tsb, cek `permits` pada rentang antara `start_date` dan `end_date` untuk pegawai terkait (`permits.employee_id = users.id`). Jika ada, tampilkan status izin berdasarkan `permits.permit_type_id`; jika tidak ada izin, tandai `tanpa keterangan`.
  - Mode `check`: tampilkan ikon hijau untuk hadir dan ikon “-” merah untuk tidak hadir (izin/tanpa keterangan dibedakan via tooltip/label kecil).
  - Mode `jumlah shift`: tampilkan angka jumlah entri `attendances` pada tanggal tsb (bisa >1 per hari).
- Grafik Pendukung:
  - Diagram batang atau pie: total `Hadir` vs `Tidak Hadir` untuk rentang & filter aktif.
  - Opsional grafik kedua yang memecah `Tidak Hadir` menjadi `Sakit`, `Izin Lain`, `Tanpa Keterangan`.

## Arsitektur Backend
- Sumber data:
  - `attendances` (lihat struktur di `docs/examples/structure_report_feature.sql`).
  - `permits` + `permit_types` untuk alasan ketidakhadiran.
  - `users`, `departemens`, `shift_kerjas` untuk filter.
- Service baru: `app/Services/Reports/AttendancePresenceService.php`
  - Input: rentang tanggal, departemen, shift, status (semua/hadir/tidak hadir), mode tampilan (check/jumlah shift).
  - Output: matriks `[user_id => { name, cells: [date => {present_count, permit_type, absent_reason}] }]` dan agregasi untuk grafik.
  - Logika:
    - Ambil kandidat user sesuai filter departemen dan (opsional) shift kerja (gunakan `users.shift_kerja_id` atau join `attendances` bila filter shift diterapkan pada fakta kehadiran).
    - Query `attendances` untuk rentang tanggal; grup per `user_id` + `date`; hitung `present_count` untuk mode jumlah shift.
    - Jika `present_count == 0`, cari `permits` dengan `status='approved'` yang mencakup tanggal (antara `start_date` dan `end_date`); tetapkan `permit_type` bila ada, jika tidak ada tetapkan `absent_reason='tanpa keterangan'`.
    - Terapkan filter `Hadir/Tidak Hadir/semua` pada hasil akhir (untuk `Tidak Hadir`, termasuk yang berizin seperti `sakit`).
- Endpoint API (opsional, bila ingin konsumsi via JS/Chart):
  - Tambah di `App\Http\Controllers\Api\ReportController`: `attendancePresenceMatrix()` mengembalikan matriks + agregasi.
  - Route di `routes/api.php`: `GET /reports/attendance-presence`.
  - Catatan: Di repo sudah ada `attendanceReport` (lihat `App\Http\Controllers\Api\ReportController@attendanceReport`), fitur baru tidak mengubah endpoint tsb.

## Arsitektur Frontend (Filament)
- Halaman baru: `app/Filament/Pages/AttendancePresenceReport.php` + view `resources/views/filament/pages/attendance-presence-report.blade.php`.
  - Form filter memakai komponen Filament: `Select`, `Toggle`, `DatePicker`.
  - Tabel dinamis dirender via komponen Livewire kustom untuk fleksibilitas kolom tanggal.
  - Gunakan Chart.js (via Filament `ChartWidget` atau in-page script) untuk grafik.
- Alternatif Legacy Blade (jika diakses via layout lama):
  - View Blade baru di `resources/views/pages/reports/attendance-presence.blade.php` yang menggunakan asset Chart.js yang sudah ada (`public/library/chart.js`).

## Sidebar & Navigasi
- Tambah item menu pada Blade sidebar: `resources/views/components/sidebar.blade.php` ke rute laporan baru.
- Registrasi halaman di navigasi Filament (title: "Laporan Kehadiran").

## Rincian Data & Query
- Efisiensi query:
  - Satu query agregasi `attendances` per rentang: `SELECT user_id, date, COUNT(*) as present_count ... GROUP BY user_id, date`.
  - Query `permits` per rentang dengan `status='approved'` dan map ke setiap tanggal di rentang.
  - Preload `users` dengan filter departemen dan (opsional) lokasi/shift.
- Aturan bisnis asumsi (dapat diubah):
  - `permits.status` harus `approved` untuk dianggap ketidakhadiran berizin.
  - Ketidakhadiran tanpa entri `attendances` dan tanpa `permits` -> `tanpa keterangan`.
  - Filter shift: jika dipilih, hanya nilai kehadiran dengan `attendances.shift_id` sesuai; pengguna tanpa kehadiran pada shift tsb akan tampak `tidak hadir`.

## Validasi & Keamanan
- Validasi parameter filter: tanggal wajib format valid dan `start_date <= end_date`, status & mode memiliki nilai yang diizinkan.
- Batasi output (pagination/virtualization) bila rentang tanggal/gagal performa.
- Pastikan pengguna yang mengakses halaman memiliki peran admin/HR sesuai kebijakan (Sanctum/Fortify/Filament auth).

## Performa & UX
- Batasi maksimum rentang tanggal (mis. 31 hari) untuk menjaga ukuran tabel.
- Jadikan tabel scroll horizontal dengan header lengket.
- Debounce perubahan filter sebelum pemuatan ulang data.
- Cache ringan untuk agregasi grafik berdasarkan filter.

## Langkah Implementasi
1) Backend service `AttendancePresenceService` untuk menyusun matriks & agregasi.
2) Tambahkan method `attendancePresenceMatrix` di `Api\ReportController` + route `GET /reports/attendance-presence`.
3) Buat Filament Page `AttendancePresenceReport` dengan form filter & Livewire komponen tabel dinamis.
4) Tambahkan widget/Chart.js untuk grafik hadir vs tidak hadir pada halaman.
5) Tambah link menu ke sidebar Blade (`resources/views/components/sidebar.blade.php`) dan registrasi navigasi Filament.
6) Uji dengan data nyata: berbagai kombinasi filter, mode `check`/`jumlah shift`, dan validasi izin sakit/tanpa keterangan.

## Berkas yang Diubah/Ditambah
- `app/Services/Reports/AttendancePresenceService.php` (baru)
- `app/Http/Controllers/Api/ReportController.php` (tambah method)
- `routes/api.php` (tambah route)
- `app/Filament/Pages/AttendancePresenceReport.php` (baru)
- `resources/views/filament/pages/attendance-presence-report.blade.php` (baru)
- `resources/views/components/sidebar.blade.php` (tambah item menu)
- Opsional: `resources/views/pages/reports/attendance-presence.blade.php` untuk legacy Blade

## Verifikasi
- UAT: cek ikon & angka sesuai mode, kolom tanggal sesuai rentang, filter departemen/shift bekerja.
- Grafik cocok dengan agregasi tabel.
- Cek performa untuk rentang tanggal besar; sesuaikan batas bila perlu.
- Pastikan izin `sakit` muncul sebagai ketidakhadiran berizin; tanpa entri & tanpa izin dicatat `tanpa keterangan`.
