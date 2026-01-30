<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamKerja extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
        'is_cross_day',
        'grace_period_minutes',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_cross_day' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($model) {
            \Illuminate\Support\Facades\Log::info("JamKerja created by user " . (auth()->id() ?? 'system'), $model->toArray());
        });

        static::updated(function ($model) {
            \Illuminate\Support\Facades\Log::info("JamKerja updated by user " . (auth()->id() ?? 'system'), [
                'id' => $model->id,
                'changes' => $model->getChanges(),
                'original' => $model->getOriginal(),
            ]);
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Log::info("JamKerja deleted by user " . (auth()->id() ?? 'system'), $model->toArray());
        });
    }

    // No relationships for now as it's a new master data
}
