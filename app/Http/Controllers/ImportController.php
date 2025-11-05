<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <-- ¡AÑADIDO PARA DEBUGGING!
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function create()
    {
        return Inertia::render('Terminals/Import');
    }

    public function store(Request $request)
    {
        // Opcional, pero recomendado: Aumentar los límites de tiempo/memoria temporalmente
        // ini_set('max_execution_time', 300); // 5 minutos
        // ini_set('memory_limit', '512M');    // 512 MB

        Log::info('--- INICIO DE IMPORTACIÓN DE TERMINALES ---');

        $request->validate(['terminals_file' => 'required|mimes:xlsx,xls,xlsm']);
        $file = $request->file('terminals_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();
            Log::info('Hojas encontradas: ' . implode(', ', $sheetNames));

            foreach ($sheetNames as $sheetName) {
                $trimmedSheetName = trim($sheetName);
                Log::info("Intentando procesar hoja: '{$trimmedSheetName}'");

                $package = Package::where('name', $trimmedSheetName)->first();
                
                if (!$package) {
                    Log::warning("Hoja ignorada: '{$trimmedSheetName}' (No coincide con ninguna tarifa/package)");
                    continue;
                }
                
                Log::info("Hoja válida: '{$trimmedSheetName}'. ID del paquete: {$package->id}");

                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $rows = $worksheet->toArray();
                $terminalRows = array_slice($rows, 2); // Excluir las dos primeras filas (cabeceras)

                Log::info("Total de filas de datos a procesar en '{$trimmedSheetName}': " . count($terminalRows));

                $dataToUpsert = [];
                $validRowsCount = 0;
                $rowNumber = 3; // Empezamos en la fila 3 del Excel

                foreach ($terminalRows as $row) {
                    $brand           = $row[0] ?? null; // Columna A
                    $model           = $row[1] ?? null; // Columna B
                    $duration_months = $row[3] ?? null; // Columna D
                    $initial_cost    = $row[5] ?? 0;   // Columna F
                    $monthly_cost    = $row[6] ?? 0;   // Columna G
                    $initial_cost_discount = $row[7] ?? 0;   // Columna H
                    $monthly_cost_discount = $row[8] ?? 0;   // Columna I

                    if (empty($brand) || empty($model) || !is_numeric($duration_months)) {
                        // Log::debug("Fila {$rowNumber} ignorada por datos inválidos.");
                        $rowNumber++;
                        continue;
                    }

                    $terminal = Terminal::updateOrCreate(
                        ['model' => trim($model)],
                        ['brand' => trim($brand)]
                    );

                    // Preparamos los datos para una inserción masiva y eficiente
                    $dataToUpsert[] = [
                        'package_id'      => $package->id,
                        'terminal_id'     => $terminal->id,
                        'duration_months' => $duration_months,
                        'initial_cost'    => is_numeric($initial_cost) ? $initial_cost : 0,
                        'monthly_cost'    => is_numeric($monthly_cost) ? $monthly_cost : 0,
                        'initial_cost_discount' => is_numeric($initial_cost_discount) ? $initial_cost_discount : 0,
                        'monthly_cost_discount' => is_numeric($monthly_cost_discount) ? $monthly_cost_discount : 0,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    $validRowsCount++;
                    $rowNumber++;
                }

                Log::info("Total de registros listos para 'upsert' para '{$trimmedSheetName}': {$validRowsCount}");

                if (!empty($dataToUpsert)) {
                    DB::table('package_terminal')->upsert(
                        $dataToUpsert,
                        ['package_id', 'terminal_id', 'duration_months'],
                        ['initial_cost', 'monthly_cost','initial_cost_discount', 'monthly_cost_discount',  'updated_at']
                    );
                    Log::info("UPSERT completado para '{$trimmedSheetName}'.");
                }
            }
            
            Log::info('--- FINALIZACIÓN EXITOSA DE IMPORTACIÓN DE TERMINALES ---');

        } catch (\Exception $e) {
            Log::error('ERROR FATAL DE IMPORTACIÓN: ' . $e->getMessage(), [
                'file' => $e->getFile(), 
                'line' => $e->getLine()
            ]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage() . ' en la línea ' . $e->getLine());
        }
        
        return redirect()->route('terminals.import.create')->with('success', '¡Terminales importados!');
    }
}