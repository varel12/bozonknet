<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSubscription extends Model
{
    protected $fillable = [
        'odp_id',
        'name',
        'whatsapp',
        'email',
        'billing_day',
        'customer_type',
        'village_id',
        'package_id',
        'street_address',
        'full_address',
        'village_name',
        'latitude',
        'longitude',
        'coverage_status',
        'plan_code',
        'plan_name',
        'speed_mbps',
        'monthly_price',
        'installation_fee',
        'status',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'consented_at' => 'datetime',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }
}
