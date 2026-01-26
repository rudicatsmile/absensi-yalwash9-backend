# Catatan Dokumentasi

## Cara Menghilangkan Menu dari Sidebar

Bagian ini menjelaskan langkah-langkah untuk menyembunyikan menu halaman atau resource dari sidebar navigasi Filament tanpa menghapus fungsionalitasnya.

### 1. Analisis Kode

Pada Filament, setiap _Page_ atau _Resource_ memiliki properti statis yang mengontrol registrasi navigasi. Secara default, jika properti ini tidak didefinisikan, Filament akan mendaftarkannya ke navigasi.

### 2. Identifikasi Metode

Properti yang mengontrol visibilitas navigasi adalah `$shouldRegisterNavigation`.

### 3. Langkah-langkah Implementasi

1.  **Buka File:**
    Buka file _Page_ atau _Resource_ yang ingin Anda sembunyikan.
    Contoh: `app/Filament/Pages/EmployeeWorkSchedule.php`

2.  **Tambahkan Properti:**
    Tambahkan kode berikut di dalam class:
    ```php
    protected static bool $shouldRegisterNavigation = false;
    ```

### 4. Contoh Kode

**Sebelum Perubahan:**

```php
class EmployeeWorkSchedule extends Page
{
    protected static ?string $navigationLabel = 'Pengaturan Izin Masuk';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    // ... kode lainnya
}
```

**Sesudah Perubahan:**

```php
class EmployeeWorkSchedule extends Page
{
    protected static ?string $navigationLabel = 'Pengaturan Izin Masuk';

    // Tambahkan baris ini untuk menghilangkan menu dari sidebar
    protected static bool $shouldRegisterNavigation = false;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    // ... kode lainnya
}
```

### 5. Penjelasan Tambahan

Dengan mengatur `$shouldRegisterNavigation` menjadi `false`, Filament tidak akan merender item navigasi untuk halaman ini di sidebar. Namun, halaman tersebut masih dapat diakses melalui URL langsung atau redirect dari halaman lain (seperti yang dilakukan pada `EmployeeWorkSchedule` yang me-redirect ke index UserResource).

### 6. Penjelasan Mendapatkan angka tidak hadir, pada Menu Laporan -> Laporan Kehadiran dan Tidak Hadir

filePath: d:\Softwares\projects\mobile\yalwash9\laravel-absensi-backend\app\Filament\Widgets\PresenceSummaryWidget.php
filePath: d:\Softwares\projects\mobile\yalwash9\laravel-absensi-backend\app\Services\Reports\AttendancePresenceService.php

Berikut adalah analisis mendalam mengenai baris kode `d:\Softwares\projects\mobile\yalwash9\laravel-absensi-backend\app\Filament\Widgets\PresenceSummaryWidget.php#L46-46`:

```php
$absentUnexcused = (int) ($totals['absent_unexcused'] ?? 0);
```

### 1. Sumber Data

Variabel `$absentUnexcused` tidak diambil langsung dari query database di dalam widget ini, melainkan merupakan hasil agregasi dari **Service Class** `App\Services\Reports\AttendancePresenceService`.

Service tersebut mengambil data dari tabel-tabel berikut:

- **`users`** (Model `User`): Daftar pegawai aktif.
- **`attendances`** (Model `Attendance`): Data kehadiran/presensi harian.
- **`permits`** (Model `Permit`): Data izin yang sudah disetujui (`status = 'approved'`).
    - _Catatan:_ Data **Cuti** (`leaves`) **TIDAK** digunakan dalam perhitungan variabel ini oleh Service tersebut.

### 2. Logika Perhitungan

Nilai ini dihitung melalui metode `buildMatrix` di `AttendancePresenceService`. Algoritmanya bekerja dengan membuat matriks **Pegawai x Tanggal** dalam rentang periode yang dipilih.

**Langkah-langkah Logika:**

1.  Ambil semua Pegawai (`users`).
2.  Ambil semua Presensi (`attendances`) dalam rentang tanggal.
3.  Ambil semua Izin (`permits`) yang **approved** dalam rentang tanggal, **KECUALI** `permit_type_id = 4` (yang kemungkinan adalah tipe Cuti atau khusus lainnya).
4.  Iterasi setiap **Pegawai** untuk setiap **Tanggal**:
    - Cek apakah ada data **Presensi**?
        - **YA**: Hitung sebagai Hadir.
        - **TIDAK**:
            - Cek apakah ada data **Izin** (selain tipe 4)?
                - **YA**: Hitung sebagai `absent_by_permit` (Izin).
                - **TIDAK**: Hitung sebagai `absent_unexcused` (Alpa/Tanpa Keterangan).
5.  Akumulasikan total `absent_unexcused` dari seluruh pegawai dan tanggal.

**Penting:** Karena Service ini mengecualikan `permit_type_id = 4` dan tidak mengecek tabel `leaves`, maka pegawai yang sedang **Cuti** akan terhitung sebagai **Alpa (`absent_unexcused`)** dalam variabel ini (sebelum ada penyesuaian manual di widget).

### 3. Query Raw (SQL Simulation)

Berikut adalah gambaran query SQL mentah yang dijalankan oleh Service di balik layar:

**a. Mengambil User:**

```sql
SELECT id, name, departemen_id, shift_kerja_id
FROM users
WHERE departemen_id = ?; -- Jika filter departemen aktif
```

**b. Mengambil Presensi (Attendance):**

```sql
SELECT user_id, date
FROM attendances
WHERE date BETWEEN '2023-01-01' AND '2023-01-31'
AND shift_id = ?; -- Jika filter shift aktif
```

**c. Mengambil Izin (Permit):**

```sql
SELECT employee_id, permit_type_id, start_date, end_date, status
FROM permits
WHERE status = 'approved'
AND permit_type_id != 4 -- Mengecualikan tipe tertentu (misal: Cuti)
AND end_date >= '2023-01-01'
AND start_date <= '2023-01-31';
```

### 4. Parameter Input

Nilai `$absentUnexcused` dipengaruhi oleh parameter sesi (session) berikut:

- `apr_start_date` & `apr_end_date`: Rentang tanggal laporan.
- `apr_departemen_id`: Filter pegawai berdasarkan departemen.
- `apr_shift_id`: Filter pegawai/presensi berdasarkan shift kerja.

### 5. Struktur Output

Outputnya adalah sebuah **Integer** tunggal yang merepresentasikan **Total Hari-Orang (Man-Days)** ketidakhadiran tanpa keterangan.

- Contoh: `5` berarti ada total 5 kejadian ketidakhadiran tanpa izin (bisa 1 orang tidak masuk 5 hari, atau 5 orang tidak masuk 1 hari).

### Contoh Konkret Perhitungan

**Skenario Data Dummy:**

- **Periode**: 1 Januari 2024 (1 Hari)
- **Pegawai A**: Tidak Absen, Tidak Ada Izin.
- **Pegawai B**: Melakukan Absen (Hadir).
- **Pegawai C**: Tidak Absen, Ada Izin Sakit (Permit Type 1).
- **Pegawai D**: Tidak Absen, Sedang Cuti (Leave / Permit Type 4).

**Proses Kalkulasi oleh Service:**

| Pegawai | Cek Presensi | Cek Izin (Kecuali Tipe 4)  | Hasil Klasifikasi Service | Kontribusi ke `absent_unexcused` |
| :------ | :----------- | :------------------------- | :------------------------ | :------------------------------- |
| **A**   | Kosong       | Kosong                     | **Alpa**                  | +1                               |
| **B**   | Ada          | (Diabaikan)                | Hadir                     | +0                               |
| **C**   | Kosong       | Ada (Sakit)                | Izin                      | +0                               |
| **D**   | Kosong       | Kosong (Tipe 4 di-exclude) | **Alpa**                  | +1                               |

**Hasil Akhir `$absentUnexcused`:** `2` (Pegawai A + Pegawai D).

//$absentUnexcused = $absentUnexcused - $absentByPermit - $totalCutiRecords;
$absentUnexcused = $absentUnexcused - $totalCutiRecords;

_Catatan: Pada kode widget saat ini, angka Cuti (Pegawai D) masuk ke dalam kategori "Tidak Hadir" (Alpa) kecuali jika Anda menerapkan logika pengurangan `$totalCutiRecords` dari `$absentUnexcused` seperti yang direncanakan sebelumnya._
