<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Propuesta Oferta #{{ $offer->id }}</title>
    <style>
        /* Estilos generales */
        @page {
            margin: 120px 50px;
        }

        body {
            font-family: 'Helvetica', DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
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
            margin-top: 25px;
            margin-bottom: 10px;
        }
        
        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 15px; /* Espacio después de cada tabla */
        }
        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            width: 30%; /* Ancho por defecto para la columna de "concepto" */
        }
        td {
            width: 70%;
        }
        
        /* Tabla de resumen de precios */
        .summary-table th {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .summary-table td {
            border: 1px solid #ddd;
        }
        
        /* Tabla de líneas móviles */
        .lines-table th, .lines-table td {
             border: 1px solid #ddd;
             text-align: center;
             font-size: 9px;
             width: auto; /* Dejar que la tabla de líneas se auto-ajuste */
        }
        .lines-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .lines-table td:nth-child(4) { /* Columna Terminal */
            text-align: left;
        }

        /* Utilidades de texto */
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .italic { font-style: italic; }
        .text-gray { color: #555; }
        
        .summary-total-value { font-size: 20px; font-weight: bold; color: #000; }
        .description-text { font-size: 10px; color: #555; text-align: justify; margin-bottom: 15px; }
        
        /* Listas (para beneficios, TV, etc.) */
        ul {
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 3px;
        }

    </style>
</head>
<body>

    @php
        // --- Definimos todas las variables replicando la lógica de Show.vue ---
        $allAddons = $offer->addons ?? collect();
        
        // --- Internet y TV ---
        $baseInternetAddon = $allAddons->firstWhere('type', 'internet');
        $ipFijaPrincipal = $allAddons->firstWhere(fn($a) => $a->type === 'internet_feature' && $a->pivot->addon_name === 'IP Fija');
        $fibraOroPrincipal = $allAddons->firstWhere(fn($a) => $a->type === 'internet_feature' && $a->pivot->addon_name === 'Fibra Oro');
        $additionalInternetLines = $allAddons->where('type', 'internet_additional');
        $tvAddons = $allAddons->whereIn('type', ['tv', 'tv_base', 'tv_premium']);
        
        // --- Centralita (Agrupación simple por tipo) ---
        $centralitaBase = $allAddons->firstWhere('type', 'centralita');
        $centralitaFeature = $allAddons->firstWhere('type', 'centralita_feature');
        $centralitaExtensions = $allAddons->where('type', 'centralita_extension');
        
        // --- Soluciones y Beneficios ---
        $digitalSolutions = $allAddons->whereIn('type', ['service', 'software']);
        $appliedBenefits = $offer->benefits ?? collect();
    @endphp

    <header>
        <table class="no-border-table">
            <tr>
                <td style="width: 50%;" class="header-logo">orange</td>
                <td style="width: 50%; text-align: right; vertical-align: bottom;">
                    <b style="font-size: 16px;">Propuesta Pack NEGOCIO</b><br>
                    Oferta Código: {{ $offer->id }}
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
        <table class="summary-table">
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
        <table class="summary-table">
            <tr>
                <th style="font-size: 16px;">Cuota Neta Mensual</th>
                <td class="text-right summary-total-value">{{ number_format($offer->summary['finalPrice'] ?? 0, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <th>Pago Inicial Total (Equipamiento)</th>
                <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($offer->summary['totalInitialPayment'] ?? 0, 2, ',', '.') }} €</td>
            </tr>
        </table>
        
        <h2>Desglose de Precios Mensuales</h2>
        <table class="summary-table">
             <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right">Importe</th>
                </tr>
             </thead>
             <tbody>
                @forelse ($offer->summary['summaryBreakdown'] ?? [] as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="text-right font-bold {{ $item['price'] < 0 ? 'text-gray' : '' }}">
                            {{ $item['price'] >= 0 ? '+' : '' }}{{ number_format($item['price'], 2, ',', '.') }} €
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center italic">No hay desglose de precios disponible.</td></tr>
                @endforelse
             </tbody>
        </table>

    </main>

    <div class="page-break"></div>

    <h1>Detalle de Servicios Contratados</h1>

    <h2>Líneas Móviles y Terminales</h2>
    <table class="lines-table">
        <thead>
            <tr>
                <th>Nº Línea</th>
                <th>Nº Teléfono</th>
                <th>Tipo</th>
                <th>Operador Origen</th>
                <th>Terminal Asociado</th>
                <th class="text-right">Pago Inicial</th>
                <th class="text-right">Cuota/Mes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offer->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line->phone_number ?: 'Nuevo' }}</td>
                    <td>{{ $line->is_extra ? 'Adicional' : 'Principal' }}</td>
                    <td>{{ $line->is_portability ? $line->source_operator : 'Nuevo' }}</td>
                    <td>{{ $line->terminal_name ?: 'Sin terminal' }}</td>
                    <td class="text-right">{{ number_format($line->initial_cost, 2, ',', '.') }} €</td>
                    <td class="text-right">{{ number_format($line->monthly_cost, 2, ',', '.') }} €</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center italic">No hay líneas móviles configuradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Internet y Televisión</h2>
    <table>
        <tr>
            <th>Fibra Principal</th>
            <td>
                {{ $baseInternetAddon->pivot->addon_name ?? 'N/A' }}
                @if($ipFijaPrincipal)
                    <br><span class="italic text-gray" style="font-size: 9px;">+ Incluye IP Fija Principal</span>
                @endif
                @if($fibraOroPrincipal)
                    <br><span class="italic text-gray" style="font-size: 9px;">+ Incluye Fibra Oro Principal</span>
                @endif
            </td>
        </tr>

        @if($additionalInternetLines->isNotEmpty())
            <tr>
                <th>Internet Adicional</th>
                <td>
                    <ul>
                        @foreach($additionalInternetLines as $line)
                            <li>
                                <b>{{ $line->pivot->addon_name }}</b>
                                @if($line->pivot->has_ip_fija)
                                    <span class="italic text-gray" style="font-size: 9px;">(con IP Fija)</span>
                                @endif
                                @if($line->pivot->has_fibra_oro)
                                    <span class="italic text-gray" style="font-size: 9px;">(con Fibra Oro)</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endif
        
        @if($tvAddons->isNotEmpty())
            <tr>
                <th>Televisión</th>
                <td>
                    <ul>
                        @foreach($tvAddons as $tv)
                            <li>{{ $tv->pivot->addon_name }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endif
    </table>

    @if($centralitaBase || $centralitaFeature || $centralitaExtensions->isNotEmpty())
        <h2>Detalle del Servicio de Centralita</h2>
        <table>
            @if($centralitaBase)
                <tr>
                    <th>Centralita Base</th>
                    <td>{{ $centralitaBase->pivot->addon_name }}</td>
                </tr>
            @endif
            @if($centralitaFeature)
                <tr>
                    <th>Operadora</th>
                    <td>{{ $centralitaFeature->pivot->addon_name }}</td>
                </tr>
            @endif
            @if($centralitaExtensions->isNotEmpty())
                <tr>
                    <th>Extensiones</th>
                    <td>
                        <ul>
                            @foreach($centralitaExtensions as $ext)
                                <li>{{ $ext->pivot->addon_name }} (x{{ $ext->pivot->quantity }})</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
        </table>
    @endif

    @if($digitalSolutions->isNotEmpty())
        <h2>Soluciones Digitales</h2>
        <table>
            <tbody>
                @foreach($digitalSolutions as $solution)
                    <tr>
                        <th style="width: 30%;">{{ $solution->pivot->addon_name }}</th>
                        <td>Servicio digital ({{ number_format($solution->pivot->addon_price, 2, ',', '.') }} €/mes)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($appliedBenefits->isNotEmpty())
        <h2>Beneficios Aplicados</h2>
        <table>
            <tbody>
                @foreach($appliedBenefits as $benefit)
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <span class="font-bold">✓ {{ $benefit->description }}</span>
                            @if($benefit->addon)
                                <span class="italic text-gray" style="font-size: 9px;">(Aplica a: {{ $benefit->addon->name }})</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
</body>
</html>