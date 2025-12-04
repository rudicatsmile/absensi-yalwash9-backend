## Tujuan
- Mengganti halaman laporan `attendance-presence-report` ke pendekatan modular ala Resources/Pages Filament (seperti halaman Users), tanpa membuat/menyentuh file Blade kustom.
- Menghilangkan duplikasi sidebar akibat injeksi `@vite('resources/css/app.css')`.
- Mempertahankan seluruh fungsionalitas: filter periode/shift/departemen, matriks kehadiran, ringkasan counters, ekspor Excel/PDF, grafik ringkas.

## Rujukan Arsitektur
- Contoh modular: `UserResource`, `Pages\ListUsers`, `Schemas\UserForm`, `Tables\UsersTable` (struktur serupa akan diterapkan untuk laporan).
- Prinsip: definisi UI via PHP (Filament Forms/Tables/Widgets), tanpa Blade kustom.

## Pendekatan Teknis
1. **Page Modular (tanpa Blade)**
   - Buat `Reports\Pages\AttendancePresenceReport` (turunan `Filament\Pages\Page`) tanpa properti `$view` kustom.
   - Gunakan `InteractsWithPageFilters` agar filter reaktif dan auto-refresh.
   - Dedikasikan submodul:
     - `Reports\Schemas\AttendancePresenceFilterForm` untuk filter (tanggal awal/akhir, departemen, shift, status, mode)
     - `Reports\Tables\AttendancePresenceMatrixTable` untuk matriks kehadiran dengan kolom dinamis (per tanggal)
     - `Reports\Widgets\PresenceSummaryWidget` (Chart donut Hadir/Izin/Tidak Hadir)
     - `Reports\Widgets\AbsenceBreakdownWidget` (Chart pie rincian ketidakhadiran)
     - `Reports\Actions\ExportReportAction` (aksi ekspor Excel/PDF)
   - Semua komponen ditulis dalam PHP, mengikuti pola halaman Users sehingga modular, mudah dirawat.

2. **Matriks Kehadiran (kolom dinamis tanpa Blade)**
   - Override `getTableColumns()` dalam `AttendancePresenceMatrixTable` untuk membangkitkan `TextColumn` berdasarkan rentang tanggal (mengambil dari filter form).
   - Gunakan `formatStateUsing` untuk menampilkan tanda hadir/izin/tidak hadir sesuai data matriks, tanpa HTML bebas bila memungkinkan (gunakan badge/ikon bawaan Filament untuk aksesibilitas).

3. **Grafik Ringkas (tanpa Blade kustom)**
   - Gunakan `Filament\Widgets\ChartWidget` untuk dua grafik.
   - Data diambil dari layanan/method yang sama seperti versi lama (totals present/absent_by_permit/absent_unexcused) agar perilaku identik.
   - Tidak menambahkan file Blade; ChartWidget menggunakan templating internal Filament.

4. **Filter & Counters**
   - Form filter di `AttendancePresenceFilterForm` (tanggal, departemen, shift, status, mode) dengan `->reactive()` dan `->afterStateUpdated()` yang memicu `$this->dispatch('refresh-widgets')`.
   - Ringkasan counters (Hadir/Izin/Tidak Hadir) disajikan via `StatsOverviewWidget` atau Infolist cards agar konsisten dengan Dashboard.

5. **Ekspor Excel/PDF**
   - Aksi header `ExportReportAction` memanggil endpoint yang sudah ada, menyusun query string dari state filter. Tombol non-blocking dengan umpan balik UI bawaan Filament.
   - Tidak ada Blade; aksi di-deklarasikan di Page sebagai `HeaderActions`.

6. **Manajemen CSS & Duplikasi Sidebar**
   - Hapus ketergantungan `@vite('resources/css/app.css')` di konteks report; tidak ada injeksi CSS via Blade → duplikasi sidebar hilang.
   - Styling mengikuti tema Filament; jika perlu variasi gaya ringan, gunakan utility classes bawaan Filament/Tailwind dari komponen (tanpa file CSS global baru).
   - Opsional: jika perlu gaya khusus, daftarkan melalui mekanisme Panel/Theme Filament secara global (bukan per-halaman) untuk mencegah konflik; namun target awal: tanpa CSS tambahan.

7. **Kompatibilitas & Performa**
   - Query data (matriks dan totals) menggunakan service/helper yang sama dengan implementasi lama untuk mencegah regresi.
   - Kolom dinamis dikonstruksi efisien (hindari ratusan kolom dengan fallback mode ringkasan).
   - Hindari DOM-manipulation JS kustom (seperti toggle sidebar via localStorage); gunakan kemampuan panel Filament untuk konsistensi.

8. **Pengujian**
   - Uji fungsional Livewire/Filament: perubahan filter memicu refresh widget/table, data identik (snapshot totals/matriks vs versi lama).
   - Uji ekspor: pastikan URL dan parameter tersusun sama, response Excel/PDF sukses.
   - Uji akses: role-based visibility mengikuti kebijakan yang sama seperti Users/Dashboard.

9. **Langkah Implementasi Bertahap**
   - Analisis & ekstraksi logika dari Blade lama ke service/data provider.
   - Buat Page + submodul (Schemas/Tables/Widgets/Actions) secara incremental.
   - Sambungkan filter → data → widgets → table.
   - Validasi visual dan perilaku terhadap versi lama.

10. **Kriteria Keberhasilan**
   - Fitur bekerja identik, tanpa duplikasi UI.
   - Tidak ada Blade kustom baru.
   - Performa setara/lebih baik.
   - Kode modular mudah dikelola.
   - Kompatibel dengan arsitektur Filament yang ada.

Catatan: Permintaan "React/Vue/JSX" akan dipenuhi secara efektif melalui komponen deklaratif Filament (yang merupakan pendekatan modern dan modular di ekosistem Laravel Admin). Jika integrasi React/Vue murni tetap diwajibkan, alternatifnya adalah memasang mikro-komponen React/Vue melalui asset panel Filament dan mounting ke container dalam `ChartWidget`/render hooks tanpa menambah Blade kustom; ini akan dijadikan fase opsional setelah versi modular Filament stabil.