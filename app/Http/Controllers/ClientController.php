<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// La línea conflictiva ha sido eliminada de aquí

class ClientController extends Controller
{
    /**
     * Muestra una lista de clientes filtrada por el rol del usuario.
     */
    public function index()
    {
        $user = Auth::user();

        // Se inicia la consulta base para los clientes
        $clientsQuery = Client::with('user:id,name');

        if ($user->isManager()) {
            // El jefe de equipo/ventas ve los clientes de todos los miembros de su equipo
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);

        } elseif ($user->role === 'user') {
            // Un usuario normal solo ve sus propios clientes
            $clientsQuery->where('user_id', $user->id);
        }
        // Si el rol es 'admin', no se aplica ningún filtro y verá todos los clientes.

        return Inertia::render('Clients/Index', [
            'clients' => $clientsQuery->latest()->paginate(10)
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo cliente.
     */
    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * Guarda un nuevo cliente en la base de datos.
     */
    public function store(Request $request)
    {
        $rules = [
            'source' => 'nullable|string|in:offers',
            'type' => 'required|in:empresa,autonomo',
            'cif_nif' => 'required|string|max:20|unique:clients,cif_nif',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:20',
            'door' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
        ];

        if ($request->input('type') === 'empresa') {
            $rules['name'] = 'required|string|max:255';
        } else { // 'autonomo'
            $rules['first_name'] = 'required|string|max:255';
            $rules['last_name'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        $clientData = $validated;
        if ($validated['type'] === 'autonomo') {
            $clientData['name'] = $validated['first_name'] . ' ' . $validated['last_name'];
        }

        // Se asigna el ID del usuario autenticado al nuevo cliente
        $clientData['user_id'] = Auth::id();

        $client = Client::create($clientData);

        // Lógica de redirección basada en el origen
        if ($request->input('source') === 'offers') {
            return redirect()->route('offers.create', ['new_client_id' => $client->id])->with('success', 'Cliente creado y listo para usar en la oferta.');
        } else {
            return redirect()->route('clients.index')->with('success', 'Cliente creado con éxito.');
        }
    }
    
    public function show(Client $client)
    {
        // Puedes implementar la lógica para mostrar un cliente individual si lo necesitas
    }

    /**
     * Muestra el formulario para editar un cliente existente.
     */
    public function edit(Client $client)
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client
        ]);
    }

    /**
     * Actualiza un cliente en la base de datos.
     */
    public function update(Request $request, Client $client)
    {
        $rules = [
            'type' => 'required|in:empresa,autonomo',
            'cif_nif' => 'required|string|max:20|unique:clients,cif_nif,' . $client->id,
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:20',
            'door' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
        ];

        if ($request->input('type') === 'empresa') {
            $rules['name'] = 'required|string|max:255';
        } else { // 'autonomo'
            $rules['first_name'] = 'required|string|max:255';
            $rules['last_name'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        $clientData = $validated;
        if ($validated['type'] === 'autonomo') {
            $clientData['name'] = $validated['first_name'] . ' ' . $validated['last_name'];
        }

        $client->update($clientData);

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Elimina un cliente de la base de datos.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
    
    /**
     * Muestra las ofertas asociadas a un cliente.
     */
    public function showOffers(Client $client)
    {
        $offers = $client->offers()
                          ->with(['package', 'user.team'])
                          ->latest()
                          ->paginate(10);

        return Inertia::render('Clients/ShowOffers', [
            'client' => $client,
            'offers' => $offers,
        ]);
    }
}