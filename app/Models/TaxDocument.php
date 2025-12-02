<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxDocument extends Model
{
    protected $fillable = ['dian_code', 'description', 'abbreviation', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
