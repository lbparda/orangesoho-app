<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'package_id',
        'client_id',
        'summary',
        'user_id',
        'probability',
        'signing_date',
        'processing_date',

        // --- INICIO CAMPOS SNAPSHOT AÑADIDOS ---
        'package_name',
        'package_price',
        'package_commission',
        'status',
        // --- FIN CAMPOS SNAPSHOT ---
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'summary' => 'array',
        'signing_date' => 'date',
        'processing_date' => 'date',

        // --- INICIO CASTS AÑADIDOS ---
        'package_price' => 'decimal:2',
        'package_commission' => 'decimal:2',
        // --- FIN CASTS ---
    ];

    /**
     * Get the package associated with the offer.
     * Mantenemos esta relación para referencia, aunque los datos principales
     * se leen ahora desde los campos snapshot (ej. package_name).
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who created the offer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lines for the offer.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OfferLine::class);
    }

    /**
     * The addons that belong to the offer.
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class)
            ->withPivot([
                'quantity',
                'has_ip_fija',
                'selected_centralita_id',
                
                // --- INICIO CAMPOS SNAPSHOT PIVOTE AÑADIDOS ---
                'addon_name',
                'addon_price',
                'addon_commission',
                'has_fibra_oro' // <-- AÑADIDO
            ])
            ->withTimestamps(); // Buena práctica si tu tabla pivote tiene timestamps
    }

    /**
     * Get the client associated with the offer.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
