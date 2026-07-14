<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_EXPANSION = 'expansion';

    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = ['name', 'district', 'latitude', 'longitude', 'status'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
