<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable = ['name', 'hex_code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
