<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class DiscountImportController extends Controller
{
    /**
     * Muestra la página del formulario de importación.
     */
    public function showImportForm()
    {
        return Inertia::render('Admin/Discounts/Import');
    }

    /**
     * Procesa el archivo CSV subido.
     */
    public function storeCsv(Request $request)
    {
        // 1. Validar el archivo
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        try {
            // 2. Abrir y leer el archivo
            $fileHandle = fopen($path, 'r');
            if (!$fileHandle) {
                throw new Exception("No se pudo abrir el archivo CSV.");
            }

            // 3. Leer la cabecera (primera fila) para obtener los nombres de las columnas
            // --- MODIFICADO: Añadir el delimitador ';' ---
            $header = array_map('trim', fgetcsv($fileHandle, 0, ';')); 
            
            // Validar cabeceras esperadas
            $expectedHeaders = ['id', 'name', 'percentage', 'duration_months', 'conditions'];
            if (count(array_diff($expectedHeaders, $header)) > 0) {
                 fclose($fileHandle);
                 throw new Exception("El CSV no tiene las columnas esperadas. Asegúrate de que contiene: id, name, percentage, duration_months, conditions");
            }

            $rowCount = 0;
            // 4. Leer el resto de filas, una por una
            // --- MODIFICADO: Añadir el delimitador ';' ---
            while (($row = fgetcsv($fileHandle, 0, ';')) !== false) {
                
                // --- INICIO: CORRECCIÓN DE ERROR ---
                // Comprobamos que la fila no esté vacía (ej. una línea en blanco al final)
                if (empty(array_filter($row))) {
                    continue; // Saltar esta fila vacía
                }

                // Comprobamos que el número de columnas de la fila coincida con la cabecera
                if (count($header) !== count($row)) {
                    fclose($fileHandle);
                    throw new Exception(
                        "Error en la fila " . ($rowCount + 2) . ". " . // +2 porque +1 es la cabecera y +1 es el índice 0
                        "El número de columnas (" . count($row) . ") no coincide con la cabecera (" . count($header) . "). " .
                        "Revisa si hay comas o saltos de línea extra en tu CSV."
                    );
                }
                // --- FIN: CORRECCIÓN DE ERROR ---

                // Combinar la cabecera con la fila para obtener un array asociativo
                $data = array_combine($header, $row);

                // 5. Limpiar y preparar los datos
                $id = !empty($data['id']) ? (int)$data['id'] : null;
                $conditions = !empty($data['conditions']) ? json_decode($data['conditions'], true) : null;
                
                // Si el JSON es inválido, json_decode devuelve null.
                if (!empty($data['conditions']) && $conditions === null) {
                    throw new Exception("Error en la fila " . ($rowCount + 2) . ": El JSON de 'conditions' no es válido.");
                }

                // 6. Usar updateOrCreate para actualizar o crear
                Discount::updateOrCreate(
                    [
                        'id' => $id // Busca por ID (si es null, creará uno nuevo)
                    ],
                    [ // Datos para actualizar o crear
                        'name' => $data['name'],
                        'percentage' => (float)$data['percentage'],
                        'duration_months' => (int)$data['duration_months'],
                        'conditions' => $conditions,
                    ]
                );
                $rowCount++;
            }

            fclose($fileHandle);

        } catch (Exception $e) {
            // Si algo sale mal (JSON inválido, archivo corrupto...)
            return redirect()->route('admin.discounts.importCsv')
                             ->with('error', 'Error al importar: ' . $e->getMessage());
        }

        // 7. Éxito
        return redirect()->route('admin.discounts.index')
                         ->with('success', "¡Importación completada! Se procesaron $rowCount descuentos.");
    }
}