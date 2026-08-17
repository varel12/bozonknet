<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternetPackage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'speed_mbps',
        'price',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'speed_mbps' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
