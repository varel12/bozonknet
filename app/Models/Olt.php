<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'location_description',
    ];

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class);
    }
}
