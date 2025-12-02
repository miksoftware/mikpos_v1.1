<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imei extends Model
{
    protected $fillable = ['imei', 'imei2', 'status', 'notes'];
}
