<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PymePackage extends Model
{
    use HasFactory;

    protected $table = 'pyme_packages';

    protected $fillable = [
        'name',
        'base_price',
        'commission_optima',
        'commission_custom',
        'commission_porta',
        'bonus_cp_24',
        'bonus_cp_36',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'commission_optima' => 'decimal:2',
        'commission_custom' => 'decimal:2',
        'commission_porta' => 'decimal:2',
        'bonus_cp_24' => 'decimal:2',
        'bonus_cp_36' => 'decimal:2',
    ];

    // Relación con terminales en modalidad VAP
    public function terminalsVap(): BelongsToMany
    {
        return $this->belongsToMany(PymeTerminal::class, 'pyme_package_terminal_vap', 'pyme_package_id', 'pyme_terminal_id')
            ->withPivot('duration_months', 'initial_cost', 'monthly_cost')
            ->withTimestamps();
    }

    // Relación con terminales en modalidad Subvención
    public function terminalsSub(): BelongsToMany
    {
        return $this->belongsToMany(PymeTerminal::class, 'pyme_package_terminal_sub', 'pyme_package_id', 'pyme_terminal_id')
            ->withPivot('duration_months', 'cession_price', 'subsidy_price')
            ->withTimestamps();
    }
}