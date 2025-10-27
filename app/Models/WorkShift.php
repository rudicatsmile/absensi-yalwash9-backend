<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkShift Model
 * 
 * Manages work shift data including shift times, cross-day settings,
 * grace periods, and user assignments through many-to-many relationships.
 * 
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_cross_day
 * @property int $grace_period_minutes
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkShift extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'work_shifts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_cross_day',
        'grace_period_minutes',
        'is_active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_cross_day' => 'boolean',
        'is_active' => 'boolean',
        'grace_period_minutes' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_cross_day' => false,
        'is_active' => true,
        'grace_period_minutes' => 0,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the users assigned to this work shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_shift_user')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active work shifts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive work shifts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include cross-day work shifts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCrossDay($query)
    {
        return $query->where('is_cross_day', true);
    }

    /**
     * Get formatted start time for display.
     *
     * @return string
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return date('H:i', strtotime($this->start_time));
    }

    /**
     * Get formatted end time for display.
     *
     * @return string
     */
    public function getFormattedEndTimeAttribute(): string
    {
        return date('H:i', strtotime($this->end_time));
    }

    /**
     * Get shift duration in hours.
     *
     * @return float
     */
    public function getDurationHoursAttribute(): float
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        
        // Handle cross-day shifts
        if ($this->is_cross_day && $end < $start) {
            $end += 24 * 60 * 60; // Add 24 hours
        }
        
        return ($end - $start) / 3600;
    }

    /**
     * Get the number of users assigned to this shift.
     *
     * @return int
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Check if the shift is currently active based on time.
     *
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $this->start_time;
        $end = $this->end_time;

        if ($this->is_cross_day) {
            // For cross-day shifts, check if current time is after start OR before end
            return $now >= $start || $now <= $end;
        } else {
            // For same-day shifts, check if current time is between start and end
            return $now >= $start && $now <= $end;
        }
    }

    /**
     * Get shift status for display.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        return $this->isCurrentlyActive() ? 'Active' : 'Scheduled';
    }

    /**
     * Get formatted duration for display.
     *
     * @return string
     */
    public function getDurationFormattedAttribute(): string
    {
        $hours = $this->duration_hours;
        return number_format($hours, 0) . ' jam';
    }
}