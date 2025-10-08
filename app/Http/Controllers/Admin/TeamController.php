<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Teams/Index', [
            'teams' => Team::withCount('users')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Teams/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Team::create($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Equipo creado correctamente.');
    }
}