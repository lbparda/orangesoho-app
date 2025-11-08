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
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        try {
            $fileHandle = fopen($path, 'r');
            if (!$fileHandle) {
                throw new Exception("No se pudo abrir el archivo CSV.");
            }

            // Leer cabecera (usando ';')
            $header = array_map('trim', fgetcsv($fileHandle, 0, ';')); 
            
            // --- AÑADIDO 'is_active' A LAS CABECERAS ESPERADAS ---
            $expectedHeaders = ['id', 'name', 'percentage', 'duration_months', 'conditions', 'is_active'];
            $diff = array_diff($expectedHeaders, $header);
            
            if (count($diff) > 0) {
                 fclose($fileHandle);
                 throw new Exception("El CSV no tiene las columnas esperadas. Faltan: " . implode(', ', $diff));
            }

            $rowCount = 0;
            // Leer filas (usando ';')
            while (($row = fgetcsv($fileHandle, 0, ';')) !== false) {
                
                if (empty(array_filter($row))) {
                    continue; // Saltar filas vacías
                }

                // --- INICIO: CORRECCIÓN DE ERROR DE SINTAXIS ---
                // Comprobamos que el número de columnas de la fila coincida con la cabecera
                if (count($header) !== count($row)) {
                    fclose($fileHandle);
                    // Arregladas las comillas (") que faltaban
                    throw new Exception(
                        "Error en la fila " . ($rowCount + 2) . ". " . 
                        "El número de columnas (" . count($row) . ") no coincide con la cabecera (" . count($header) . ")."
                    );
                }
                // --- FIN: CORRECCIÓN DE ERROR DE SINTAXIS ---

                $data = array_combine($header, $row);

                $id = !empty($data['id']) ? (int)$data['id'] : null;
                $conditions = !empty($data['conditions']) ? json_decode($data['conditions'], true) : null;
                
                // --- INICIO: CORRECCIÓN DE ERROR DE SINTAXIS ---
                if (!empty($data['conditions']) && $conditions === null) {
                    // Arregladas las comillas (") que faltaban
                    throw new Exception("Error en la fila " . ($rowCount + 2) . ": El JSON de 'conditions' no es válido.");
                }
                // --- FIN: CORRECCIÓN DE ERROR DE SINTAXIS ---

                // --- AÑADIDO: Lógica para 'is_active' (convierte '1' a true, '0' o vacío a false) ---
                $isActive = isset($data['is_active']) && (strtolower($data['is_active']) === '1' || strtolower($data['is_active']) === 'true');

                Discount::updateOrCreate(
                    [
                        'id' => $id // Busca por ID (si es null, crea uno nuevo)
                    ],
                    [ // Datos para actualizar o crear
                        'name' => $data['name'],
                        'percentage' => (float)$data['percentage'],
                        'duration_months' => (int)$data['duration_months'],
                        'conditions' => $conditions,
                        'is_active' => $isActive, // <-- AÑADIDO
                    ]
                );
                $rowCount++;
            }

            fclose($fileHandle);

        } catch (Exception $e) {
            return redirect()->route('admin.discounts.importCsv')
                             ->with('error', 'Error al importar: ' . $e->getMessage());
        }

        return redirect()->route('admin.discounts.index')
                         ->with('success', "¡Importación completada! Se procesaron $rowCount descuentos.");
    }
}