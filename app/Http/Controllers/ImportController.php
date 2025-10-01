<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $request->validate(['terminals_file' => 'required|mimes:xlsx,xls,xlsm']);
        $file = $request->file('terminals_file');

        Log::channel('daily')->info('--- INICIANDO NUEVA IMPORTACIÓN DE TERMINALES ---');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();
            Log::info('Hojas encontradas en el Excel: ' . implode(', ', $sheetNames));

            foreach ($sheetNames as $sheetName) {
                $trimmedSheetName = trim($sheetName);
                Log::info("--- Procesando hoja: '{$trimmedSheetName}' ---");

                $package = Package::where('name', $trimmedSheetName)->first();
                if (!$package) {
                    Log::warning("AVISO: No se encontró paquete para la hoja '{$trimmedSheetName}'.");
                    continue;
                }
                
                Log::info("Paquete encontrado: ID={$package->id}");

                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $rows = $worksheet->toArray();
                $terminalRows = array_slice($rows, 7);

                foreach ($terminalRows as $rowIndex => $row) {
                    // --- LÓGICA DE LECTURA ACTUALIZADA ---
                    $brand           = $row[0] ?? null; // Columna A
                    $model           = $row[1] ?? null; // Columna B
                    $duration_months = $row[3] ?? null; // Columna D (MESES)
                    $initial_cost    = $row[5] ?? 0;   // Columna F
                    $monthly_cost    = $row[6] ?? 0;   // Columna G

                    if (empty($brand) || empty($model) || !is_numeric($duration_months)) {
                        continue;
                    }

                    $terminal = Terminal::updateOrCreate(
                        ['model' => trim($model)],
                        ['brand' => trim($brand)]
                    );
                    
                    Log::info("Terminal: ID={$terminal->id}, Modelo={$terminal->model}, Meses={$duration_months}");

                    // La clave: Sincronizamos la relación para el terminal y paquete, usando los meses como condición
                    $package->terminals()->syncWithoutDetaching([
                        $terminal->id => [
                            'duration_months' => $duration_months,
                            'initial_cost' => is_numeric($initial_cost) ? $initial_cost : 0,
                            'monthly_cost' => is_numeric($monthly_cost) ? $monthly_cost : 0,
                        ]
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("ERROR CRÍTICO: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
        
        Log::info('--- IMPORTACIÓN FINALIZADA CON ÉXITO ---');
        return redirect()->route('terminals.import.create')->with('success', '¡Terminales importados!');
    }
}