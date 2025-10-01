<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    /**
     * Muestra el formulario para subir el archivo.
     */
    public function showForm()
    {
        return Inertia::render('Terminals/Import');
    }

    /**
     * Procesa el archivo Excel importado.
     */
    public function import(Request $request)
    {
        $request->validate(['terminals_file' => 'required|mimes:xlsx,xls,xlsm']);
        $file = $request->file('terminals_file');

        try {
            // 1. Cargar el archivo Excel
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 2. Procesar los encabezados de las filas 5 y 6
            $tariff_header = $rows[4]; // Fila 5 en Excel
            $payment_header = $rows[5]; // Fila 6 en Excel
            
            $packageColumnMap = [];
            // Mapeo de los nombres en el Excel a los nombres en tu base de datos
            $tariffToPackages = [
                'BASE PLUS NEGOCIO Extra 1' => ['Base Plus', 'NEGOCIO Extra 1'],
                'NEGOCIO Extra 3' => ['NEGOCIO Extra 3'],
                'NEGOCIO Extra 5' => ['NEGOCIO Extra 5'],
                'NEGOCIO EXTRA 10 NEGOCIO EXTRA 20' => ['NEGOCIO Extra 10', 'NEGOCIO Extra 20'],
            ];

            foreach ($payment_header as $index => $payment_type) {
                if (empty($payment_type) || empty($tariff_header[$index])) continue;

                $tariff_name = $tariff_header[$index];
                if (array_key_exists($tariff_name, $tariffToPackages)) {
                    if (!isset($packageColumnMap[$tariff_name])) {
                        $packageColumnMap[$tariff_name] = [
                            'packages' => Package::whereIn('name', $tariffToPackages[$tariff_name])->get()
                        ];
                    }
                    
                    if (stripos($payment_type, 'Inicial') !== false) {
                        $packageColumnMap[$tariff_name]['initial_index'] = $index;
                    } elseif (stripos($payment_type, 'Cuota') !== false) {
                        $packageColumnMap[$tariff_name]['monthly_index'] = $index;
                    }
                }
            }
            
            // 3. Empezar a leer los terminales desde la Fila 8 (índice 7)
            $terminalRows = array_slice($rows, 7);

            foreach ($terminalRows as $row) {
                $brand = $row[1] ?? null; // Columna B
                $model = $row[3] ?? null; // Columna D
                if (empty($brand) || empty($model)) continue;

                // 4. Crear o actualizar el terminal en la tabla `terminals`
                $terminal = Terminal::updateOrCreate(
                    ['model' => $model],
                    ['brand' => $brand]
                );

                // 5. Limpiar precios antiguos para este terminal para evitar duplicados
                $terminal->packages()->detach();

                // 6. Asignar los nuevos precios por paquete
                foreach ($packageColumnMap as $tariffInfo) {
                    // Verificamos que los índices de las columnas existan
                    if (isset($tariffInfo['initial_index']) && isset($tariffInfo['monthly_index'])) {
                        $initial_payment = $row[$tariffInfo['initial_index']] ?? 0;
                        $monthly_fee = $row[$tariffInfo['monthly_index']] ?? 0;

                        if ($tariffInfo['packages']->isNotEmpty()) {
                            $terminal->packages()->attach($tariffInfo['packages']->pluck('id'), [
                                'initial_payment' => $initial_payment,
                                'monthly_fee'     => $monthly_fee,
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Devolvemos un error claro que incluye el mensaje y la línea
            return redirect()->back()->withErrors(['terminals_file' => 'Error: ' . $e->getMessage() . ' en la línea ' . $e->getLine()]);
        }
        
        return redirect()->back()->with('success', '¡Terminales y precios por paquete importados con éxito!');
    }
}