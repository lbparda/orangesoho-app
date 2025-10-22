<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- AÑADIDO
use App\Models\User; // <-- AÑADIDO

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'first_name',
        'last_name',
        'street_number',
        'floor',
        'door',
        'postal_code',
        'city',
        'name', 
        'cif_nif', 
        'contact_person', 
        'email', 
        'phone', 
        'address',
        'user_id' // <-- Asegúrate de que user_id esté en $fillable
    ];

    /**
     * Define la relación: un cliente puede tener muchas ofertas.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Define la relación: un cliente pertenece a un usuario.
     * // <-- ESTA ES LA FUNCIÓN QUE FALTABA
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}