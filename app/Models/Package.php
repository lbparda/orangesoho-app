<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function terminals(): BelongsToMany
    {
        return $this->belongsToMany(Terminal::class, 'package_terminal')
            // MODIFICACIÓN CRÍTICA: Se añade 'id' para que el guardado funcione
            ->withPivot('id', 'initial_cost', 'monthly_cost', 'duration_months','included_line_commission', 'additional_line_commission')
            ->withTimestamps();
    }
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'addon_package')->withPivot('price', 'is_included', 'included_quantity', 'line_limit','included_line_commission', 'additional_line_commission')->withTimestamps();
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'package_discount');
    }

    public function o2oDiscounts(): BelongsToMany
    {
        return $this->belongsToMany(O2oDiscount::class, 'o2o_discount_package')->withPivot('subsidy_percentage', 'dho_payment', 'osp_payment')->withTimestamps();
    }
}

