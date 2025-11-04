# Data Flow: AttendanceReport

## Variabel dan Sumber
- `start_date`, `end_date`: diikat dari form `DatePicker` (baris 49–66) ke properti kelas via `->live()` dan `->afterStateUpdated`.
- `shift_id`: diikat dari `Select` (baris 67–75) ke properti kelas via `->afterStateUpdated`.

## Alur Penggunaan
1. Form → Properti kelas: nilai filter disimpan di `$this->start_date`, `$this->end_date`, `$this->shift_id`.
2. Sanitasi/Validasi: `sanitizeAndValidateInputs()` memastikan tipe/nilai valid dan mengisi fallback bila perlu.
3. Konsumsi:
   - Header Actions/Export/Generate: menggunakan nilai yang telah disanitasi (contoh di baris 103–105).
   - Query Matrix:
     - Mode `source=php`: `getMatrixData($filters)`.
     - Mode `source=sql`: `getMatrixDataFromSqlFile($filters)` dengan fallback jika file tidak tersedia/gagal dieksekusi.

## Validasi
- Tanggal: format `Y-m-d`, urutan benar; fallback ke periode bulan berjalan.
- Shift: numeric dan harus ada di DB; selain itu di-nul-kan.

## Fallback & Error Handling
- File SQL tidak tersedia/tidak terbaca: log + notifikasi, fallback ke generator PHP.
- Eksekusi SQL gagal: log + notifikasi, fallback ke generator PHP.
- `per_page` dan `page`: dinormalisasi agar tidak memicu error pagination.

## Dependensi
- DB: `Illuminate\Support\Facades\DB`
- Waktu: `Carbon\Carbon`
- Notifikasi UI: `Filament\Notifications\Notification`
- Model: `App\Models\ShiftKerja`