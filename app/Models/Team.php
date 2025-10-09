<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'commission_percentage',
    ];

    /**
     * Get the users for the team.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}