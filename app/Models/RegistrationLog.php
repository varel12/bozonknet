<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationLog extends Model
{
    protected $fillable = [
        'customer_name',
        'phone_number',
        'village_name',
        'latitude',
        'longitude',
        'package_id',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'payload' => 'array',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }
}
