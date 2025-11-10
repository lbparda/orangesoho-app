<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    protected $fillable = [
        'description',
        'category',
        'apply_type',
        'apply_value',
        'addon_id',
        
    ];

    // Un beneficio ES un producto
    public function addon()
    {
        return $this->belongsTo(Addon::class);
    }

    // Un beneficio es ofrecido por muchos Paquetes
    public function packages()
    {
        return $this->belongsToMany(Package::class, 'benefit_package');
    }

    // Un beneficio puede ser usado en muchas Ofertas
    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'benefit_offer');
    }
}