<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Terminal extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_terminal')->withPivot('initial_payment', 'monthly_fee')->withTimestamps();
    }
}