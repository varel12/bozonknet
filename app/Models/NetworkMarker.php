<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkMarker extends Model
{
    protected $fillable = [
        'odp_id',
        'odc_id',
        'customer_subscription_id',
        'type',
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
