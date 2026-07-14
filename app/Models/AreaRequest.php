<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaRequest extends Model
{
    protected $fillable = [
        'name',
        'address',
        'whatsapp',
        'latitude',
        'longitude',
        'coverage_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
