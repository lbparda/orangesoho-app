<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- ESTA LÍNEA FALTABA
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cif_nif',
        'contact_person',
        'email',
        'phone',
        'address',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}