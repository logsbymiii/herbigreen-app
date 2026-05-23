<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // akun admin
        User::firstOrCreate(
            ['email' => 'admin@herbigreen.test'],
            [
                'name' => 'Helmi Admin',
                'password' => Hash::make('sukses123'),
            ]
        );

        // panggil seeder dari faker
        $this->call([
            DivisionSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
