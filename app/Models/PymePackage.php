<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PymePackage extends Model
{
    use HasFactory;

    // Definimos explícitamente la tabla para asegurar que es independiente
    protected $table = 'pyme_packages';

    protected $fillable = [
        'name',
        'base_price',
        'commission_optima',
        'commission_custom',
        'commission_porta',
        'bonus_cp_24',
        'bonus_cp_36',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'commission_optima' => 'decimal:2',
        'commission_custom' => 'decimal:2',
        'commission_porta' => 'decimal:2',
        'bonus_cp_24' => 'decimal:2',
        'bonus_cp_36' => 'decimal:2',
    ];
}