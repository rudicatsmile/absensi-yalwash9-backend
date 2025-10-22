<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyLocation merepresentasikan satu lokasi perusahaan.
 * Field: company_id, name, address, latitude, longitude, radius_km.
 */
class CompanyLocation extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_km',
        'attendance_type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'company_location_user',
            'company_location_id',
            'user_id'
        )->withTimestamps();
    }
}