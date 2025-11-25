<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        'image_path',
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

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'notify_at' => 'datetime',
            'cancelled' => 'boolean',
            'notified' => 'boolean',
        ];
    }

    public function storeImage(UploadedFile $file): bool
    {
        try {
            $path = Storage::disk('public')->putFile('religious-study-events', $file);
            $this->image_path = $path;
            return true;
        } catch (\Throwable $e) {
            \Log::error('model:religious-study-events.store-image.failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }
}
