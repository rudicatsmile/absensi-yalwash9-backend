**Ringkasan**
- Menambahkan fitur pengaturan izin masuk kerja per pegawai per bulan/tahun, tersimpan dalam JSON, diakses melalui daftar pegawai yang menyerupai halaman Users, dengan modal edit jadwal dan audit log. Fokus pada admin sebagai aktor utama, responsif dan aksesibel.

**Database & Model**
- Tabel baru: employee_work_schedule
  - Kolom: id (PK), employee_id (FK → users.id, cascade), month (1–12), year (>=2020), allowed_days (JSON)
  - Indeks unik: employee_id + month + year
- Migration: create_employee_work_schedule
- Model: App\Models\EmployeeWorkSchedule
  - Relasi: user() → belongsTo(User::class, 'employee_id')
  - casts(): ['allowed_days' => 'array']
  - Validasi domain: month 1..12, year range masuk akal

**Integrasi UI (Filament v4)**
- Lokasi: extend halaman Users (UsersTable) agar sesuai spesifikasi tampilan
- Kolom daftar:
  - Nomor urut (seperti implementasi existing row_number)
  - Nama lengkap (name/email seperti di UsersTable)
  - Departemen (departemen.name)
  - Shift kerja: tampilkan shift aktif (read-only). Dropdown disediakan di modal untuk perubahan; hindari update inline karena relasi shift existing cukup kompleks.
  - Action: tombol Ubah (Filament\Actions\Action) untuk membuka modal edit jadwal
- Akses tombol Ubah: visible hanya untuk admin (ikuti pola kontrol akses di proyek)

**Modal Edit Jadwal**
- Form komponen:
  - Select bulan (Januari–Desember)
  - Number input tahun (default tahun berjalan)
  - Select shift (Pagi/Siang/Malam) – menyetel jadwal untuk shift terpilih; tidak mengubah relasi shift global user
  - Kalender bulanan:
    - Representasi grid minggu/hari dengan checkbox per tanggal (default checked)
    - Hari Minggu diberi warna merah (gunakan View component kustom + Tailwind, bind ke state allowed_days)
- Tombol aksi: Simpan, Batal
- Loading state: Filament action menyediakan loading indicator otomatis

**Logika Simpan & Validasi**
- Ambil record schedule: employee_id + month + year; buat jika belum ada
- Serialisasi allowed_days sebagai array boolean keyed by tanggal ('1'..'31') agar efisien
- Hanya update yang berubah:
  - Bandingkan old vs new allowed_days
  - Jika sebagian berubah, gunakan JSON_SET (MySQL) melalui DB::statement untuk path yang berubah; jika banyak, lakukan update penuh kolom JSON
- Audit log:
  - Log::info dengan actor_id, employee_id, month, year, diff (tanggal yang berubah)
- Notifikasi:
  - Sukses/gagal menggunakan Filament\Notifications\Notification

**Aksesibilitas & Responsif**
- Label terasosiasi (for/aria-label) untuk checkbox tanggal
- Navigasi keyboard: fokus berurutan pada elemen tanggal, tombol Simpan/Batal dapat diakses keyboard
- Responsif: grid kalender adaptif (Tailwind grid), tetap nyaman di mobile

**Pengujian (PHPUnit + Livewire)**
- Feature tests:
  - Membuka halaman Users, memastikan tombol Ubah terlihat untuk admin
  - Memanggil action Ubah dengan kombinasi bulan/tahun: semua bulan, termasuk Februari (cek tahun kabisat)
  - Verifikasi penyimpanan allowed_days untuk shift Pagi/Siang/Malam
  - Edge cases: pegawai tanpa shift (tetap bisa set jadwal), bulan 28/29/30/31 hari
  - Notifikasi sukses/gagal, audit log tertulis
- Data setup: factories untuk User dan EmployeeWorkSchedule

**Keamanan & Performa**
- Batasi akses edit jadwal ke admin (dan role lain jika diperlukan mengikuti konvensi proyek)
- Gunakan transaksi saat menulis
- Hindari N+1 saat daftar; gunakan eager load departemen

**Rencana Implementasi**
1. Buat migration + model EmployeeWorkSchedule (casts, relasi)
2. Extend UsersTable: tambahkan Action Ubah (visible admin), kolom yang diminta
3. Implementasi modal form: bulan, tahun, shift, kalender (View + binding array)
4. Simpan: diff JSON, JSON_SET partial update, transaksi, audit log, notifikasi
5. Responsif & aksesibilitas: kelas Tailwind & atribut aria
6. Tests: fitur & edge cases, jalankan satu-per-satu

Konfirmasi: Apakah kita lanjut dengan pendekatan ini (modal di halaman Users)? Jika Anda ingin halaman khusus terpisah (resource baru) alih-alih modal, saya siapkan opsi itu juga.