<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PymeAddon extends Model
{
    use HasFactory;
    
    // Tabla de addons PYME
    protected $table = 'pyme_addons';

    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'commission',
        'decommission',
    ];

    // Aplicamos casting para asegurar el formato de decimales.
    protected $casts = [
        'price' => 'decimal:2',
        'commission' => 'decimal:2',
        'decommission' => 'decimal:2',
    ];

    /**
     * Relación muchos a muchos con PymePackage.
     * Usa la tabla pivot 'pyme_addon_pyme_package'.
     */
    public function pymePackages(): BelongsToMany
    {
        return $this->belongsToMany(
            PymePackage::class, 
            'pyme_addon_package', 
            'pyme_addon_id', 
            'pyme_package_id'
        )
        ->withPivot([
            'price',
            'is_included',
            'included_quantity',
            'line_limit',
            'included_line_commission',
            'additional_line_commission'
        ])
        ->withTimestamps();
    }
}