<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PymeTerminal extends Model
{
    use HasFactory;

    protected $table = 'pyme_terminals';

    protected $fillable = [
        'brand',
        'model',
    ];

    // Relación con paquetes en modalidad VAP
    public function vaps(): BelongsToMany
    {
        return $this->belongsToMany(PymePackage::class, 'pyme_package_terminal_vap', 'pyme_terminal_id', 'pyme_package_id')
            ->withPivot('id', 'duration_months', 'initial_cost', 'monthly_cost')
            ->withTimestamps();
    }

    // Relación con paquetes en modalidad Subvención
    public function subs(): BelongsToMany
    {
        return $this->belongsToMany(PymePackage::class, 'pyme_package_terminal_sub', 'pyme_terminal_id', 'pyme_package_id')
            ->withPivot('id', 'duration_months', 'cession_price', 'subsidy_price')
            ->withTimestamps();
    }
}