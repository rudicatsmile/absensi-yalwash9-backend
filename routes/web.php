<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // redirect to /admin
    return redirect('/admin');
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::get('/admin/ajax/jam-kerjas', function () {
        return \App\Models\JamKerja::where('is_active', true)
            ->select('id', 'name', 'start_time', 'end_time')
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'start_time' => \Carbon\Carbon::parse($item->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($item->end_time)->format('H:i'),
                ];
            });
    })->name('admin.ajax.jam-kerjas');

    Route::post('/admin/ajax/save-work-schedule', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'store'])
        ->name('admin.ajax.save-work-schedule');

    Route::get('/admin/ajax/get-work-schedule', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'getSchedule'])
        ->name('admin.ajax.get-work-schedule');
});
