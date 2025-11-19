<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReligiousStudyEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'event_at',
        'notify_at',
        'location',
        'theme',
        'speaker',
        'message',
        'cancelled',
        'notified',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->notify_at) && !empty($model->event_at)) {
                $dt = \Carbon\Carbon::parse($model->event_at)->subDay()->setTime(17, 0);
                $model->notify_at = $dt;
            }
        });
    }
}
