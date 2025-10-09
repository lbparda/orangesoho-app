<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ManagementController extends Controller
{
    public function index(Request $request)
    {
        $teamLead = $request->user();

        // Asegurarse de que el jefe de equipo tenga un equipo asignado
        if (!$teamLead->team_id) {
            return redirect()->route('dashboard')->with('error', 'No tienes un equipo asignado.');
        }

        $teamMembers = User::where('team_id', $teamLead->team_id)
            ->where('id', '!=', $teamLead->id) // No mostrarse a sí mismo
            ->get();

        return Inertia::render('TeamLead/Index', [
            'teamMembers' => $teamMembers,
        ]);
    }
    
    public function edit(Request $request, User $user)
    {
        // Asegurarse de que el usuario a editar pertenece al equipo del jefe
        Gate::authorize('manage-team-member', $user);

        return Inertia::render('TeamLead/Edit', [
            'member' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('manage-team-member', $user);

        $teamLead = $request->user()->load('team');
        $maxCommission = $teamLead->team->commission_percentage;

        $validated = $request->validate([
            'commission_percentage' => "required|numeric|min:0|max:{$maxCommission}",
        ]);

        $user->update($validated);

        return redirect()->route('team-lead.users.index')->with('success', 'Comisión actualizada.');
    }
}