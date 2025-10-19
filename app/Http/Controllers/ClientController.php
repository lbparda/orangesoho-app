<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        return Inertia::render('Clients/Index', [
            'clients' => Client::latest()->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request)
    {
        // CAMBIO: Se añade 'source' a la validación
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

        $client = Client::create($clientData);

        // CAMBIO: La lógica de redirección ahora usa el marcador 'source'.
        if ($request->input('source') === 'offers') {
            return redirect()->route('offers.create', ['new_client_id' => $client->id])->with('success', 'Cliente creado y listo para usar en la oferta.');
        } else {
            return redirect()->route('clients.index')->with('success', 'Cliente creado con éxito.');
        }
    }
    
    public function show(Client $client)
    {
        //
    }

    public function edit(Client $client)
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client
        ]);
    }

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

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
    
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