<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('team')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'teams' => Team::all(),
        ]);
    }

    public function store(Request $request)
    {
        // 1. La validación de 'team_id' se cambia a 'nullable'
        //    Esto permite crear un admin sin necesidad de seleccionar un equipo en el formulario.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'team_id' => 'nullable|exists:teams,id',
            'role' => ['required', Rule::in(['user', 'team_lead', 'admin'])],
        ]);

        $teamId = $request->team_id;

        // 2. Lógica para asignar el equipo de administradores
        if ($request->role === 'admin') {
            // Buscamos el equipo. Asegúrate de que el nombre 'Administradores' es exacto.
            $adminTeam = Team::where('name', 'Administradores')->first();
            if ($adminTeam) {
                $teamId = $adminTeam->id;
            } else {
                // Si el equipo no existe, puedes retornar un error para evitar crear un admin sin equipo.
                return back()->with('error', 'El equipo "Administradores" no existe. Por favor, créalo primero.');
            }
        }

        // Si $teamId sigue siendo null aquí (porque no es admin y no se seleccionó equipo), la BD fallará si el campo no es nullable.
        // Nos aseguramos de que no sea null si el rol no es admin.
        if ($request->role !== 'admin' && is_null($teamId)) {
            return back()->withErrors(['team_id' => 'Se requiere un equipo para este rol.'])->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'team_id' => $teamId, // 3. Se usa la variable procesada
            'is_admin' => $request->role === 'admin',
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('team'), // Cargamos la relación para que esté disponible
            'teams' => Team::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        // 1. La validación de 'team_id' también se cambia a 'nullable'.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'team_id' => 'nullable|exists:teams,id',
            'role' => ['required', Rule::in(['user', 'team_lead', 'admin'])],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $teamId = $request->team_id;

        // 2. Misma lógica de asignación que en el método store
        if ($request->role === 'admin') {
            $adminTeam = Team::where('name', 'Administradores')->first();
            if ($adminTeam) {
                $teamId = $adminTeam->id;
            } else {
                return back()->with('error', 'El equipo "Administradores" no existe. Por favor, créalo primero.');
            }
        }
        
        if ($request->role !== 'admin' && is_null($teamId)) {
            return back()->withErrors(['team_id' => 'Se requiere un equipo para este rol.'])->withInput();
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'team_id' => $teamId, // 3. Se usa la variable procesada
            'is_admin' => $request->role === 'admin',
            'role' => $request->role,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
