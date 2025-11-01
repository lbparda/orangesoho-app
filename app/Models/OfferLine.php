<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id', 'is_extra', 'is_portability', 'phone_number',
        'source_operator', 'has_vap', 'o2o_discount_id',
        'package_terminal_id', 'initial_cost', 'monthly_cost',

        // --- INICIO CAMPOS SNAPSHOT AÑADIDOS ---
        'o2o_discount_name',
        'o2o_discount_amount',
        'terminal_name',
        // --- FIN CAMPOS SNAPSHOT AÑADIDOS ---
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    // --- AÑADIDO: RELACIÓN CON DESCUENTO O2O (para leer datos antiguos) ---
    // Mantenemos la relación por si acaso, pero la hacemos "soft" (no falla si se borra)
    public function o2oDiscount(): BelongsTo
    {
        return $this->belongsTo(O2oDiscount::class)->withDefault([
            'name' => $this->o2o_discount_name ?? 'Descuento eliminado',
        ]);
    }
}