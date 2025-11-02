<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Terminal extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)
            ->withPivot('initial_cost', 'monthly_cost', 'duration_months','initial_cost_discount', 'monthly_cost_discount') // UNIFICADO
            ->withTimestamps();
    }
}