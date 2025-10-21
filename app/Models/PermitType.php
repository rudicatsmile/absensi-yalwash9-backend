<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitType extends Model
{
    protected $fillable = [
        'name',
        'quota_days',
        'is_paid',
        'urut',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'urut' => 'integer',
        ];
    }

    public function permits(): HasMany
    {
        return $this->hasMany(Permit::class);
    }
}