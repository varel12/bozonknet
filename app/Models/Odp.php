<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'total_ports',
        'used_ports',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'total_ports' => 'integer',
            'used_ports' => 'integer',
        ];
    }
}
