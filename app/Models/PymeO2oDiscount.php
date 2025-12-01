<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PymeO2oDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percentage',
        'is_active',
        'penalty_12m', // Nuevo
        'penalty_24m', // Nuevo
        'penalty_36m',  // Nuevo
    ];

    protected $casts = [
        'percentage' => 'integer',
        'is_active' => 'boolean',
    ];
}