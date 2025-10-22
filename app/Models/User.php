<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Team;
use App\Models\Offer;  // <-- AÑADIDO
use App\Models\Client; // <-- AÑADIDO

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'team_id',
        'is_admin',
        'role', // <-- Añadido
        'commission_percentage', // <-- Añadido
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean', // Es buena práctica definir el tipo
        ];
    }

    /**
     * Get the team that the user belongs to.
     */
    public function team()
    {
        // Asumimos que tendrás un modelo 'Team' en App\Models\Team
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the offers for the user.
     */
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Get the clients for the user.
     * // <-- AÑADIDO
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Determina si el usuario tiene un rol de mánager (jefe de equipo o de ventas).
     * // <-- AÑADIDO
     */
    public function isManager(): bool
    {
        return in_array($this->role, ['team_lead', 'jefe de ventas']);
    }
}