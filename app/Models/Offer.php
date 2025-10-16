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

    protected $fillable = ['user_id','package_id', 'client_id','summary'];

    protected $casts = [
        'summary' => 'array',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
     // 👇 AÑADIMOS ESTA FUNCIÓN PARA LA RELACIÓN 👇
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OfferLine::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class)->withPivot('quantity');
    }
    // --- NUEVA FUNCIÓN AÑADIDA ---
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
