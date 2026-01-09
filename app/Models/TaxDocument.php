<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxDocument extends Model
{
    use HasFactory;

    protected $fillable = ['dian_code', 'description', 'abbreviation', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
