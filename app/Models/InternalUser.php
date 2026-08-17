<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalUser extends Model
{
    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'status',
    ];
}
