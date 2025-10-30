<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Addon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // MODIFICADO: Usamos $fillable en lugar de $guarded para mayor claridad y seguridad.
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'commission',
        'decommission',
    ];

    public function packages(): BelongsToMany
    {
        // MODIFICADO: Agrupamos los campos del pivot en un array para un código más limpio.
        return $this->belongsToMany(Package::class, 'addon_package')
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