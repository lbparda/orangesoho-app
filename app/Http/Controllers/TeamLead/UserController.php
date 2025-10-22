<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    public function create()
    {
        return Inertia::render('TeamLead/Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'team_id' => auth()->user()->team_id, // Asigna el equipo del jefe de equipo
            'role' => 'user', // Por defecto, los usuarios creados son de rol 'user'
        ]);

        return redirect()->route('team-lead.users.index')->with('success', 'Usuario creado correctamente.');
    }
}