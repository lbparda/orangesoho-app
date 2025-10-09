<?php

namespace App\Providers;
use App\Models\User; // <-- Añade al principio del archivo
use Illuminate\Support\Facades\Gate; // <-- Añade al principio del archivo

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ...

        Gate::define('manage-team-member', function (User $teamLead, User $member) {
            // Un jefe de equipo puede gestionar a un miembro si ambos pertenecen al mismo equipo
            // y el jefe de equipo tiene el rol 'team_lead'.
            return $teamLead->role === 'team_lead' && $teamLead->team_id === $member->team_id;
        });
    }   
}
