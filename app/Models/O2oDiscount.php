<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class O2oDiscount extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'o2o_discount_package')->withPivot('subsidy_percentage', 'dho_payment', 'osp_payment')->withTimestamps();
    }
}