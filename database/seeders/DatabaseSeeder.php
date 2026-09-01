<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cria um usuário padrão
        User::factory()->create([
            'name' => 'Avaliador Estech',
            'email' => 'admin@estech.com',
            'password' => bcrypt('password'), // a senha será "password"
        ]);
    }
}
