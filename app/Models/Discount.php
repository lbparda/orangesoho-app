<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = ['conditions' => 'array'];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_discount');
    }
}