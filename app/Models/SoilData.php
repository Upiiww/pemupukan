<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoilData extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'soil_moisture',
        'soil_temperature',
        'soil_ph',
    ];
}

