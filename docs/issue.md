# Issue Note

File: `app/Http/Controllers/Api/LeaveController.php`

### Location 1
[LeaveController.php:L97-98](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Http/Controllers/Api/LeaveController.php#L97-98)

```php
        // $year = $startDate->year;
        $year = now()->year;
```

### Location 2
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

### Location 3
[LeavesTable.php:L167-168](file:///d:/Softwares/projects/mobile/yalwash9/laravel-absensi-backend/app/Filament/Resources/Leaves/Tables/LeavesTable.php#L167-168)

```php
                            // $year = $record->start_date->year;
                            $year = now()->year;
```
