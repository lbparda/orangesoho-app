<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta #{{ $offer->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-table td:nth-child(2) {
            text-align: right;
            font-weight: bold;
        }
        .total-row td {
            font-size: 1.2em;
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .sub-item {
            padding-left: 20px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Propuesta Comercial</h1>
            <p>Oferta #{{ $offer->id }} &middot; Fecha: {{ $offer->created_at->format('d/m/Y') }}</p>
        </div>

        <div class="section">
            <div class="section-title">Datos del Cliente</div>
            <table>
                <tr>
                    <th>Nombre</th>
                    <td>{{ $offer->client->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>CIF / NIF</th>
                    <td>{{ $offer->client->cif_nif ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Resumen Económico</div>
            <table class="summary-table">
                <tr>
                    <td colspan="2"><strong>Detalle de la Cuota Mensual</strong></td>
                </tr>
                <tr>
                    <td>Precio Base del Paquete ({{ $offer->package->name }})</td>
                    <td>{{ number_format($offer->summary['basePrice'], 2, ',', '.') }} €</td>
                </tr>
                @if(isset($offer->summary['extraLinesCost']) && $offer->summary['extraLinesCost'] > 0)
                <tr>
                    <td>Coste de Líneas Móviles Adicionales</td>
                    <td>+ {{ number_format($offer->summary['extraLinesCost'], 2, ',', '.') }} €</td>
                </tr>
                @endif
                @if(isset($offer->summary['totalTerminalFee']) && $offer->summary['totalTerminalFee'] > 0)
                <tr>
                    <td>Cuotas mensuales de Terminales</td>
                    <td>+ {{ number_format($offer->summary['totalTerminalFee'], 2, ',', '.') }} €</td>
                </tr>
                @endif
                @if(isset($offer->summary['appliedO2oList']) && count($offer->summary['appliedO2oList']) > 0)
                    @foreach($offer->summary['appliedO2oList'] as $discount)
                    <tr>
                        <td class="sub-item">Descuento O2O ({{ $discount['name'] }})</td>
                        <td>- {{ number_format($discount['value'], 2, ',', '.') }} €</td>
                    </tr>
                    @endforeach
                @endif
                <tr class="total-row">
                    <td>Total Mensual (IVA Incluido)</td>
                    <td>{{ number_format($offer->summary['finalPrice'], 2, ',', '.') }} €</td>
                </tr>
                 <tr>
                    <td colspan="2" style="padding-top: 15px;"><strong>Detalle de Pagos Iniciales</strong></td>
                </tr>
                <tr class="total-row">
                    <td>Pago Inicial Total</td>
                    <td>{{ number_format($offer->summary['totalInitialPayment'], 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">Servicios Contratados</div>
             <table>
                @foreach ($offer->addons as $addon)
                    <tr>
                        <td>{{ $addon->type === 'internet' ? 'Fibra Principal' : $addon->name }}</td>
                        <td>{{ $addon->pivot->quantity > 1 ? $addon->pivot->quantity . ' unidades' : 'Incluido' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="section">
            <div class="section-title">Líneas Móviles</div>
            <table>
                <thead>
                    <tr>
                        <th>Línea</th>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Terminal Asociado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offer->lines as $index => $line)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $line->phone_number ?: 'No especificado' }}</td>
                            <td>
                                @if($line->is_portability)
                                    Portabilidad ({{ $line->source_operator }})
                                @else
                                    Número Nuevo
                                @endif
                            </td>
                            <td>
                                @if($line->terminal_details)
                                    {{ $line->terminal_details->brand }} {{ $line->terminal_details->model }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center;">No hay líneas móviles en esta oferta.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>