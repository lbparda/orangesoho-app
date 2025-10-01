<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- AÑADIR ESTE IMPORT
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

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $package = Package::where('name', trim($sheetName))->first();
                if (!$package) continue;

                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $rows = $worksheet->toArray();
                $terminalRows = array_slice($rows, 2);

                $dataToUpsert = [];

                foreach ($terminalRows as $row) {
                    $brand           = $row[0] ?? null; // Columna A
                    $model           = $row[1] ?? null; // Columna B
                    $duration_months = $row[3] ?? null; // Columna D
                    $initial_cost    = $row[5] ?? 0;   // Columna F
                    $monthly_cost    = $row[6] ?? 0;   // Columna G

                    if (empty($brand) || empty($model) || !is_numeric($duration_months)) {
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
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // --- LÓGICA CORREGIDA Y DEFINITIVA ---
                // 'upsert' inserta o actualiza múltiples filas de una sola vez.
                // Lo hará basado en la clave única (paquete, terminal, meses).
                if (!empty($dataToUpsert)) {
                    DB::table('package_terminal')->upsert(
                        $dataToUpsert,
                        ['package_id', 'terminal_id', 'duration_months'], // La clave única para identificar duplicados
                        ['initial_cost', 'monthly_cost', 'updated_at']   // Las columnas a actualizar si se encuentra un duplicado
                    );
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage() . ' en la línea ' . $e->getLine());
        }
        
        return redirect()->route('terminals.import.create')->with('success', '¡Terminales importados!');
    }
}