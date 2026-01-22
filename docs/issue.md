# Issue Note

### Total hari pengambilan cuti, jika melebihi sisa cuti, maka diambil dari sisa cuti

:: ex : Cuti melahirkan. Diambil tanggal 24 desember 2025 s/d 25 Februari 2026. Melebihi 40, maka diambil dari sisa cuti sebanyak 40 hari itu.
:: Ini di karenakan sistem belum bisa mengambil tanggal libur dan cuti bersama. ???

[LeaveController.php:L111-117](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Http/Controllers/Api/LeaveController.php#L111-117)

```php
            // return response()->json([
            //     'message' => 'Insufficient leave balance',
            //     'remaining_days' => $leaveBalance->remaining_days,
            //     'requested_days' => $totalDays,
            // ], 400);

            $totalDays = $leaveBalance->remaining_days;
```

File: `app/Filament/Resources/Leaves/Tables/LeavesTable.php`

### Saat melakukan approve cuti, maka ambil tahun dari tahun saat ini

:: Sebab kasus ambil cuti beda tahun, ex 24 desember 2025 s/d 25 Februari 2026. Maka tahun diambil dari tahun saat ini

```php
        // $year = $record->start_date->year;
        $year = now()->year;
```

[Dashboard Backend]
[LeavesTable.php:L167-168](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Filament/Resources/Leaves/Tables/LeavesTable.php#L167-168)

[API Mobile]
[LeaveController.php:L97-98](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Http/Controllers/Api/LeaveController.php#L97-98)

File: `app/Services/Reports/AttendancePresenceService.php`

### Query Izin : Jika izin bertipe dinas maka tidak di hitung ke Card Izin (2026-01-21)

```php
        $permits = Permit::query()
            ->select(['employee_id', 'permit_type_id', 'start_date', 'end_date', 'status'])
            ->where('status', 'approved')
            ->where('permit_type_id', '!=', 4) // Exclude permit_type_id 4
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->get();
```

[AttendancePresenceService.php:L38-44](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Services/Reports/AttendancePresenceService.php#L38-44)

```php
    $izinQuery = \App\Models\Permit::whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->where('status', 'approved')
            ->where('permit_type_id', '<>', 4);

```

File: `app/Filament/Widgets/DashboardStatsWidget.php`

### Update Manual Attendance (2026-01-21)

- Added `time_out` field to `ManualAttendanceResource` update form.
- Implemented auto-filling of `latlon_out` based on `latlon_in` or company location when `time_out` is set.

```php
     TimePicker::make('time_out')
        ->label('Jam Pulang')
        ->seconds(false)
        ->default($att && $att->time_out ? substr((string) $att->time_out, 0, 5) : now()->setTimezone('Asia/Jakarta')->format('H:i')),

```

File: `app/Filament/Resources/ManualAttendances/ManualAttendanceResource.php`

### Auto Attendance Creation for Permit Type 4 (2026-01-21)

**Developer:** AI Assistant
**Description:** Updated the automatic attendance creation logic for Permit Type 4 (Izin Dinas).

**Changes:**

- **Department ID:** Retrieved dynamically from the authenticated user (`$request->user()->departemen_id`) instead of hardcoded `2`.
- **Time Handling:** `time_in` and `time_out` are set to current time in `Asia/Jakarta` timezone.
- **Geolocation:** Implemented robust `getGeoLocation()` method. It attempts to use `latlon_in` from request, validates format, and falls back to Company Location 1 or hardcoded Jakarta coordinates if invalid.

**Impact:** Improves data accuracy for auto-generated attendance records by ensuring correct department linkage, local time usage, and valid GPS coordinates.

**Code Reference:**
[API mobile]
[PermitController.php:L208-225](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Http/Controllers/Api/PermitController.php#L208-225)

[Dasboard backend]
[Permittables.php:L204-213](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Filament/Resources/Permits/Tables/PermitsTable.php#L204-213)
