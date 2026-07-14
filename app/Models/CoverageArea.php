<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageArea extends Model
{
    protected $fillable = [
        'name',
        'center_latitude',
        'center_longitude',
        'available_radius_meters',
        'expansion_radius_meters',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'available_radius_meters' => 'integer',
            'expansion_radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
