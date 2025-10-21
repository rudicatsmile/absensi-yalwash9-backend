<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyLocation merepresentasikan satu lokasi perusahaan.
 * Field: company_id, name, address, latitude, longitude, radius_km.
 */
class CompanyLocation extends \Illuminate\Database\Eloquent\Model
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
}