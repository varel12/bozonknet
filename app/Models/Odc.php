<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    protected $fillable = [
        'olt_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }
}
