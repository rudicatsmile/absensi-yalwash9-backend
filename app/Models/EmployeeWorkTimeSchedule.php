<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkTimeSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_work_time_schedule';

    protected $fillable = [
        'user_id',
        'shift_id',
        'schedule_date',
        'start_time',
        'end_time',
        'jam_kerja_id',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'start_time' => 'datetime:H:i:s', // Or just string if you prefer raw time
        'end_time' => 'datetime:H:i:s',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftKerja::class, 'shift_id');
    }

    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'jam_kerja_id');
    }
}
