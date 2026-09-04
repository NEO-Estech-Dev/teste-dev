<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Account used to obtain a Sanctum token when trying the API out.
 * Credentials are documented in teste-pratico.md.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@estech.test'],
            [
                'name' => 'Demo User',
                'password' => 'password',
            ],
        );
    }
}
