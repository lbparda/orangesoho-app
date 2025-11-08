<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB; // Importar DB

class DiscountController extends Controller
{
    /**
     * Muestra el listado de descuentos.
     */
    public function index()
    {
        return Inertia::render('Admin/Discounts/Index', [
            // Mapeamos para enviar solo los datos necesarios al listado
            'discounts' => Discount::all()->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'percentage' => $discount->percentage,
                    'duration_months' => $discount->duration_months,
                ];
            }),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo descuento.
     */
    public function create()
    {
        // Simplemente renderiza la vista de creación
        return Inertia::render('Admin/Discounts/Create');
    }

    /**
     * Guarda un nuevo descuento en la base de datos.
     */
    public function store(Request $request)
    {
        // Validamos los campos (exactamente igual que en update)
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:discounts,name'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:0'],
            'conditions' => ['nullable', 'array'], // Espera un objeto/array
        ]);

        // Creamos el descuento (funciona gracias a $guarded = [])
        Discount::create($validated);

        return redirect()->route('admin.discounts.index')
                         ->with('success', 'Descuento creado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un descuento.
     */
    public function edit(Discount $discount)
    {
        // Pasamos los datos manualmente para asegurar que todos los campos lleguen
        return Inertia::render('Admin/Discounts/Edit', [
            'discount' => [
                'id' => $discount->id,
                'name' => $discount->name,
                'percentage' => $discount->percentage,
                'duration_months' => $discount->duration_months,
                'conditions' => $discount->conditions,
            ],
        ]);
    }

    /**
     * Actualiza un descuento en la base de datos.
     */
    public function update(Request $request, Discount $discount)
    {
        // Validamos los campos de tu tabla
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('discounts')->ignore($discount->id)],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:0'],
            'conditions' => ['nullable', 'array'],
        ]);

        $discount->update($validated);

        return redirect()->route('admin.discounts.index')
                         ->with('success', 'Descuento actualizado correctamente.');
    }

    /**
     * Elimina un descuento de la base de datos.
     */
    public function destroy(Discount $discount)
    {
        // Opcional: Comprobar si está en uso antes de borrar
        // if ($discount->packages()->exists()) {
        //     return redirect()->route('admin.discounts.index')
        //                      ->with('error', 'No se puede eliminar. El descuento está en uso por paquetes.');
        // }

        $discount->delete();

        return redirect()->route('admin.discounts.index')
                         ->with('success', 'Descuento eliminado correctamente.');
    }

    /**
     * Genera un archivo seeder a partir de los datos actuales de la BBDD.
     */
    public function generateSeeder()
    {
        $discounts = Discount::all();
        
        $content = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\nuse Illuminate\Support\Facades\DB;\n\nclass DiscountSeeder extends Seeder\n{\n    /**\n     * Run the database seeds.\n     */\n    public function run(): void\n    {\n        // Limpiamos la tabla antes de insertar para evitar duplicados\n        DB::table('discounts')->delete();\n\n        DB::table('discounts')->insert([\n";

        foreach ($discounts as $discount) {
            $content .= "            [\n";
            $content .= "                'name' => '" . $this->escapeQuote($discount->name) . "',\n";
            $content .= "                'percentage' => " . $discount->percentage . ",\n";
            $content .= "                'duration_months' => " . $discount->duration_months . ",\n";
            
            // Convertimos el array de conditions a un string JSON o null
            $conditions = $discount->conditions ? "'" . $this->escapeQuote(json_encode($discount->conditions)) . "'" : 'null';
            $content .= "                'conditions' => " . $conditions . ",\n";
            
            $content .= "                'created_at' => now(),\n";
            $content .= "                'updated_at' => now(),\n";
            $content .= "            ],\n";
        }

        $content .= "        ]);\n    }\n}\n";

        // Devolvemos el contenido como un archivo de texto para descargar
        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="DiscountSeeder.php.txt"',
        ]);
    }

    /**
     * Exporta los descuentos actuales a un archivo CSV.
     */
    public function exportCsv()
    {
        $discounts = Discount::all();
        $filename = "descuentos_export_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($discounts) {
            $file = fopen('php://output', 'w');
            
            // --- INICIO: CORRECCIÓN ---
            // Añadir el delimitador (;) para que coincida con el importador
            fputcsv($file, ['id', 'name', 'percentage', 'duration_months', 'conditions'], ';');

            foreach ($discounts as $discount) {
                // Añadir el delimitador (;) para que coincida con el importador
                fputcsv($file, [
                    $discount->id,
                    $discount->name,
                    $discount->percentage,
                    $discount->duration_months,
                    $discount->conditions ? json_encode($discount->conditions) : '', // Convertir JSON a string
                ], ';');
            }
            // --- FIN: CORRECCIÓN ---
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper para escapar comillas simples en los strings generados.
     */
    private function escapeQuote($string)
    {
        if ($string === null) return '';
        return str_replace("'", "\'", $string);
    }
}