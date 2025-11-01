<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrato Oferta #{{ $offer->id }}</title>
    <style>
        /* Estilos generales */
        @page {
            margin: 120px 50px;
        }

        body {
            font-family: 'Helvetica', DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        /* Encabezado y Pie de página */
        header {
            position: fixed;
            top: -100px;
            left: 0px;
            right: 0px;
            height: 80px;
        }
        
        .header-logo {
            font-weight: bold;
            font-size: 36px;
            color: #FF7900;
        }

        footer {
            position: fixed; 
            bottom: -60px; 
            left: 0px; 
            right: 0px;
            height: 50px; 
            font-size: 9px;
            color: #888;
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        .page-number:before {
            content: "Pág.: " counter(page);
        }

        /* Utilidades y Títulos */
        .page-break { page-break-after: always; }
        h1 {
            font-size: 20px;
            color: #000;
            border-bottom: 2px solid #FF7900;
            padding-bottom: 5px;
            margin-bottom: 25px;
        }
        h2 {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .no-border-table td, .no-border-table th { border: none; padding: 2px 0; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .summary-total-value { font-size: 20px; font-weight: bold; color: #000; }
        .description-text { font-size: 10px; color: #555; text-align: justify; margin-bottom: 15px; }
    </style>
</head>
<body>

    <header>
        <table class="no-border-table">
            <tr>
                <td style="width: 50%;" class="header-logo">orange</td>
                <td style="width: 50%; text-align: right; vertical-align: bottom;">
                    <b style="font-size: 16px;">Contrato Pack NEGOCIO</b><br>
                    Código de contrato: {{ $offer->id }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <div class="page-number"></div>
    </footer>

    <main>
        <h1>Propuesta Comercial</h1>
        <h2>Datos del Cliente</h2>
        <table>
            <tr>
                <th style="width: 25%;">Empresa</th>
                <td>{{ $offer->client->name ?? 'No asignado' }}</td>
            </tr>
            <tr>
                <th>CIF / NIF</th>
                <td>{{ $offer->client->cif_nif ?? 'No asignado' }}</td>
            </tr>
        </table>

        <h2>Resumen Económico</h2>
        <table>
            <tr>
                <th style="font-size: 16px;">Cuota Neta Mensual</th>
                <td class="text-right summary-total-value">{{ number_format($offer->summary['finalPrice'] ?? 0, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <th>Pago Inicial Total (Equipamiento)</th>
                <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($offer->summary['totalInitialPayment'] ?? 0, 2, ',', '.') }} €</td>
            </tr>
        </table>
        
    </main>

    <div class="page-break"></div>

    <h1>Detalle de Servicios Contratados</h1>

    <h2>Líneas Móviles y Terminales</h2>
    <table>
        <thead>
            <tr>
                <th>Nº Línea</th>
                <th>Nº Teléfono</th>
                <th>Tipo</th>
                <th>Terminal Asociado</th>
                <th class="text-right">Pago Inicial (€)</th>
                <th class="text-right">Cuota/Mes (€)</th>
                <th class="text-right">Nº Cuotas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offer->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line->phone_number ?: 'Nuevo' }}</td>
                    <td>{{ $line->is_portability ? 'Portabilidad' : 'Nuevo' }}</td>
                    {{-- INICIO CAMBIO: Leer del campo snapshot 'terminal_name' --}}
                    <td>{{ $line->terminal_name ?: 'Sin terminal' }}</td>
                    {{-- FIN CAMBIO --}}
                    
                    <td class="text-right">{{ number_format($line->initial_cost, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($line->monthly_cost, 2, ',', '.') }}</td>

                    {{-- INICIO CAMBIO: La duración ya no se lee de la relación --}}
                    <td class="text-right">-</td> 
                    {{-- NOTA: Si quieres guardar la duración, añade 'terminal_duration' a la migración de 'offer_lines' 
                         y guárdalo en el OfferController, igual que 'terminal_name'. --}}
                    {{-- FIN CAMBIO --}}
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center;">No hay líneas móviles configuradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ================================================================ --}}
    {{-- INICIO CAMBIO: LÓGICA DE CENTRALITA SIMPLIFICADA (USANDO SNAPSHOT) --}}
    {{-- ================================================================ --}}
    @php
        // Ya no es necesario mezclar con 'package->addons'.
        // Todos los addons (incluidos o contratados) están guardados en '$offer->addons'
        // con sus nombres y precios "congelados" en el pivote.
        $centralitaServices = $offer->addons
            ->whereIn('type', ['centralita', 'centralita_feature', 'centralita_extension']);
    @endphp

    @if($centralitaServices->isNotEmpty())
        <h2>Detalle del Servicio de Centralita</h2>
        <p class="description-text">
            Solución de centralita virtual para gestionar todas las llamadas de su negocio, integrando sus líneas fijas y móviles.
        </p>
        <table>
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Detalle</th>
                    <th class="text-right">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($centralitaServices as $service)
                    <tr>
                        {{-- Leer datos del addon (type) --}}
                        <td><b>{{ ucfirst(str_replace('_', ' ', $service->type)) }}</b></td>
                        
                        {{-- Leer datos del SNAPSHOT en el PIVOTE --}}
                        <td>{{ $service->pivot->addon_name }}</td>
                        <td class="text-right">{{ $service->pivot->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    {{-- ================================================================ --}}
    {{-- FIN CAMBIO: LÓGICA DE CENTRALITA --}}
    {{-- ================================================================ --}}
    
</body>
</html>
