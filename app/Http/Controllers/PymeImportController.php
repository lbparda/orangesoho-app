<?php

namespace App\Http\Controllers;

use App\Models\PymePackage;
use App\Models\PymeTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PymeImportController extends Controller
{
    public function create()
    {
        return Inertia::render('Terminals/PymeImport');
    }

    public function store(Request $request)
    {
        // Aumentar límites para archivos grandes
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        Log::info('--- INICIO IMPORTACIÓN TERMINALES PYME (Hojas por Paquete) ---');

        $request->validate(['file' => 'required|mimes:xlsx,xls,xlsm']);
        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();
            Log::info('Hojas encontradas: ' . implode(', ', $sheetNames));

            foreach ($sheetNames as $sheetName) {
                $trimmedSheetName = trim($sheetName);
                Log::info("Procesando hoja: '{$trimmedSheetName}'");

                // 1. Buscar el Paquete por el nombre de la hoja
                $package = PymePackage::where('name', $trimmedSheetName)->first();

                if (!$package) {
                    Log::warning("Hoja ignorada: '{$trimmedSheetName}' (No coincide con ningún paquete PYME)");
                    continue;
                }

                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $rows = $worksheet->toArray();
                
                // Asumimos que las 2 primeras filas son cabeceras, igual que en SOHO
                $dataRows = array_slice($rows, 2); 

                Log::info("Filas a procesar en '{$trimmedSheetName}': " . count($dataRows));

                DB::transaction(function () use ($dataRows, $package) {
                    foreach ($dataRows as $row) {
                        // Estructura esperada de columnas (Ajusta índices si tu Excel PYME es diferente)
                        // A[0]: Marca
                        // B[1]: Modelo
                        // C[2]: Tipo (VAP / SUB) <-- NECESARIO AÑADIR ESTA COLUMNA AL EXCEL O DETECTARLO
                        // D[3]: Duración (Meses)
                        // E[4]: Precio 1 (Inicial / Cesión)
                        // F[5]: Precio 2 (Mensual / Subvención)
                        
                        $brand = $row[0] ?? null;
                        $model = $row[1] ?? null;
                        // IMPORTANTE: Asumimos que la columna C (índice 2) indica si es VAP o SUB.
                        // Si no existe, habría que deducirlo o cambiar la lógica.
                        $type = strtoupper(trim($row[2] ?? 'VAP')); 
                        $duration = (int) ($row[4] ?? 24);
                        
                        if (empty($brand) || empty($model)) continue;

                        // Limpieza de precios
                        $price1 = (float) str_replace(['€', ' ', ','], ['', '', '.'], $row[6] ?? 0);
                        $price2 = (float) str_replace(['€', ' ', ','], ['', '', '.'], $row[7] ?? 0);
                        $price3 = (float) str_replace(['€', ' ', ','], ['', '', '.'], $row[5] ?? 0);
                        $price4 = (float) str_replace(['€', ' ', ','], ['', '', '.'], $row[3] ?? 0);
                        $price5 = (float) str_replace(['€', ' ', ','], ['', '', '.'], $row[8] ?? 0);
                        // 2. Buscar o Crear Terminal
                        $terminal = PymeTerminal::firstOrCreate(
                            ['model' => trim($model)],
                            ['brand' => trim($brand)]
                        );

                        // 3. Insertar en la tabla pivote correspondiente
                        if ($type === 'VAP') {
                            DB::table('pyme_package_terminal_vap')->updateOrInsert(
                                [
                                    'pyme_package_id' => $package->id,
                                    'pyme_terminal_id' => $terminal->id,
                                    'duration_months' => $duration
                                ],
                                [
                                    'initial_cost' => $price1, // Precio 1 = Inicial
                                    'monthly_cost' => $price2, // Precio 2 = Mensual
                                    'updated_at' => now(),
                                    'created_at' => now()
                                ]
                            );
                        } elseif (in_array($type, ['SUB', 'SUBVENCIONADO', 'SUBVENCION'])) {
                            DB::table('pyme_package_terminal_sub')->updateOrInsert(
                                [
                                    'pyme_package_id' => $package->id,
                                    'pyme_terminal_id' => $terminal->id,
                                    'duration_months' => $duration
                                ],
                                [
                                    'cession_price' => $price4, // Precio 1 = Cesión
                                    'subsidy_price' => $price5, // Precio 2 = Subvención
                                    'updated_at' => now(),
                                    'created_at' => now()
                                ]
                            );
                        }
                    }
                });
                Log::info("Procesamiento de '{$trimmedSheetName}' completado.");
            }

            Log::info('--- FIN IMPORTACIÓN PYME ---');
            return redirect()->back()->with('success', 'Terminales PYME importados correctamente.');

        } catch (\Exception $e) {
            Log::error('ERROR IMPORTACIÓN PYME: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}