<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class NachoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Intenta actualizar o crear el usuario basándose en el email.
        // Esto previene duplicados si se ejecuta el seeder varias veces.
        User::updateOrCreate(
            ['email' => 'ccklbparda@gmail.com'], // Clave para encontrar el registro (email único)
            [
                'name' => 'NACHO',
                'password' => Hash::make('Helloween11??'), // La contraseña se hashea automáticamente
                'is_admin' => 1, // Se establece como administrador
                'role' => 'admin', // Se asigna el rol 'admin'
                'commission_percentage' => 100, // Porcentaje de comisión
                'email_verified_at' => Carbon::now(), // Opcional: marca el email como verificado
            ]
        );

        $this->command->info('Usuario NACHO (Admin) creado/actualizado.');
    }
}