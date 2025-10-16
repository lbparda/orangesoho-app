<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Clients/Index', [
            'clients' => Client::latest()->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cif_nif' => 'required|string|max:20|unique:clients,cif_nif',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client = Client::create($validated);

        // CAMBIO CRÍTICO: Redirige a offers.create con el ID del nuevo cliente.
        // Esto fuerza a Inertia a realizar una visita completa (full page visit),
        // recargando las props de OfferController, incluyendo la lista de clientes.
        return redirect()->route('offers.create', ['new_client_id' => $client->id])->with('success', 'Cliente creado y listo para usar en la oferta.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        // Por ahora no es necesaria, la dejamos vacía.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cif_nif' => 'required|string|max:20|unique:clients,cif_nif,' . $client->id,
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
    public function showOffers(Client $client)
    {
        // Cargamos las ofertas de este cliente, paginadas, y con las relaciones
        // que usamos en la vista de listado de ofertas para mantener la consistencia.
        $offers = $client->offers()
                         ->with(['package', 'user.team'])
                         ->latest()
                         ->paginate(10);

        // Renderizamos una nueva vista de Inertia
        return Inertia::render('Clients/ShowOffers', [
            'client' => $client,
            'offers' => $offers,
        ]);
    }
}