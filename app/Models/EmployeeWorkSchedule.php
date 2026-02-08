<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkSchedule extends Model
{
    protected $table = 'employee_work_schedule';

    protected $fillable = [
        'user_id',
        'shift_id',
        'month',
        'year',
        'allowed_days',
        'jam_kerja_ids',
    ];

    protected function casts(): array
    {
        return [
            'allowed_days' => 'array',
            'jam_kerja_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftKerja::class, 'shift_id');
    }
}
