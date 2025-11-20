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
    public function index(Request $request) // <--- 1. Inyectamos Request
    {
        // Iniciamos la consulta base
        $query = User::with('team')->latest();

        // --- 2. LÓGICA DE BÚSQUEDA APLICADA ---
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
                // Si quisieras buscar por rol también:
                // ->orWhere('role', 'like', "%{$search}%");
            });
        }
        // --- FIN LÓGICA DE BÚSQUEDA ---

        // 3. Paginamos y devolvemos a la vista, incluyendo los filtros
        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate(1000)->withQueryString(), // Mantiene los parámetros en la URL
            'filters' => $request->only(['search']), // Mantiene el texto en el input del buscador
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
            $adminTeam = Team::where('name', 'Administradores')->first();
            if ($adminTeam) {
                $teamId = $adminTeam->id;
            } else {
                return back()->with('error', 'El equipo "Administradores" no existe. Por favor, créalo primero.');
            }
        }

        // Nos aseguramos de que no sea null si el rol no es admin.
        if ($request->role !== 'admin' && is_null($teamId)) {
            return back()->withErrors(['team_id' => 'Se requiere un equipo para este rol.'])->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'team_id' => $teamId,
            'is_admin' => $request->role === 'admin',
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('team'),
            'teams' => Team::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'team_id' => 'nullable|exists:teams,id',
            'role' => ['required', Rule::in(['user', 'team_lead', 'admin'])],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $teamId = $request->team_id;

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
            'team_id' => $teamId,
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