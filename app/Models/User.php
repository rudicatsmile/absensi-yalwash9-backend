<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'position',
        'department',
        'jabatan_id',
        'departemen_id',
        'shift_kerja_id',
        'company_location_id',
        'face_embedding',
        'image_url',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function notes()
    {
        return $this->hasMany(\App\Models\Note::class);
    }

    // New one-to-one relationships
    public function jabatan()
    {
        return $this->belongsTo(\App\Models\Jabatan::class, 'jabatan_id');
    }

    public function departemen()
    {
        return $this->belongsTo(\App\Models\Departemen::class, 'departemen_id');
    }

    public function shiftKerja()
    {
        return $this->belongsTo(\App\Models\ShiftKerja::class, 'shift_kerja_id');
    }

    public function shiftKerjas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        // return $this->belongsToMany(\App\Models\ShiftKerja::class, 'shift_kerja_user');
        return $this->belongsToMany(
            ShiftKerja::class,
            'shift_kerja_user',
            'user_id',
            'shift_kerja_id'
        )->withTimestamps();
    }

    public function companyLocation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanyLocation::class, 'company_location_id');
    }

    public function companyLocations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            CompanyLocation::class,
            'company_location_user',
            'user_id',
            'company_location_id'
        )->withTimestamps();
    }

    // Legacy many-to-many relationships (deprecated, for backward compatibility)
    public function jabatans()
    {
        return $this->belongsToMany(\App\Models\Jabatan::class, 'jabatan_user');
    }

    public function departemens()
    {
        return $this->belongsToMany(\App\Models\Departemen::class, 'departemen_user');
    }


    public function overtimes()
    {
        return $this->hasMany(\App\Models\Overtime::class);
    }

    public function approvedOvertimes()
    {
        return $this->hasMany(\App\Models\Overtime::class, 'approved_by');
    }

    public function shiftAssignments()
    {
        return $this->hasMany(\App\Models\ShiftAssignment::class);
    }

    public function leaves()
    {
        return $this->hasMany(\App\Models\Leave::class, 'employee_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(\App\Models\LeaveBalance::class, 'employee_id');
    }

    public function approvedLeaves()
    {
        return $this->hasMany(\App\Models\Leave::class, 'approved_by');
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'manager', 'kepala_lembaga', 'kepala_sub_bagian', 'employee'], true);
    }
}
