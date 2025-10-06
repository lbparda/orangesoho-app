<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id', 'is_extra', 'is_portability', 'phone_number', 
        'source_operator', 'has_vap', 'o2o_discount_id', 
        'package_terminal_id', 'initial_cost', 'monthly_cost'
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
