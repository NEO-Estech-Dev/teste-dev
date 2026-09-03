<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'avaliador@estech.test'],
            [
                'name' => 'Avaliador Estech',
                'password' => 'password',
            ]
        );
    }
}
