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
        'departemen_id',
        'jabatan_id',
        'departemen_ids',
        'jabatan_ids',
        'isoverlay',
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
            'departemen_ids' => 'array',
            'jabatan_ids' => 'array',
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
        if (!$this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }

    public function departemen()
    {
        return $this->belongsTo(\App\Models\Departemen::class, 'departemen_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(\App\Models\Jabatan::class, 'jabatan_id');
    }

    public function getAllDepartemenNames(): \Illuminate\Support\Collection
    {
        $ids = $this->departemen_ids;
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        $unique_ids = collect($ids)->map(fn($id) => (int) $id)->filter()->unique()->values();

        if ($unique_ids->isEmpty()) {
            return collect();
        }

        $rows = \App\Models\Departemen::whereIn('id', $unique_ids)
            ->selectRaw('MIN(id) as id, MIN(name) as name, TRIM(LOWER(name)) as norm')
            ->groupBy('norm')
            ->get();

        $order = collect($unique_ids)->values()->all();
        $sorted = $rows->sortBy(function ($row) use ($order) {
            $pos = array_search((int) $row->id, $order, true);
            return $pos === false ? PHP_INT_MAX : $pos;
        });

        return $sorted->pluck('name')->map(fn($name) => trim($name))->filter()->values();
    }

    public function getAllJabatanNames(): \Illuminate\Support\Collection
    {
        $ids = $this->jabatan_ids;
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        $unique_ids = collect($ids)->map(fn($id) => (int) $id)->filter()->unique()->values();

        if ($unique_ids->isEmpty()) {
            return collect();
        }

        $rows = \App\Models\Jabatan::whereIn('id', $unique_ids)
            ->selectRaw('MIN(id) as id, MIN(name) as name, TRIM(LOWER(name)) as norm')
            ->groupBy('norm')
            ->get();

        $order = collect($unique_ids)->values()->all();
        $sorted = $rows->sortBy(function ($row) use ($order) {
            $pos = array_search((int) $row->id, $order, true);
            return $pos === false ? PHP_INT_MAX : $pos;
        });

        return $sorted->pluck('name')->map(fn($name) => trim($name))->filter()->values();
    }
}
