# Catatan Dokumentasi

## Cara Menghilangkan Menu dari Sidebar

Bagian ini menjelaskan langkah-langkah untuk menyembunyikan menu halaman atau resource dari sidebar navigasi Filament tanpa menghapus fungsionalitasnya.

### 1. Analisis Kode
Pada Filament, setiap *Page* atau *Resource* memiliki properti statis yang mengontrol registrasi navigasi. Secara default, jika properti ini tidak didefinisikan, Filament akan mendaftarkannya ke navigasi.

### 2. Identifikasi Metode
Properti yang mengontrol visibilitas navigasi adalah `$shouldRegisterNavigation`.

### 3. Langkah-langkah Implementasi

1.  **Buka File:**
    Buka file *Page* atau *Resource* yang ingin Anda sembunyikan.
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
