<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Division;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $divisions = Division::all();


        Employee::create([
            'division_id' => $divisions->first()->id,
            'name' => 'Helmi Fajari',
            'phone' => '6285606178752', // PENTING: Harus mulai dari 628
            'is_active' => true,
        ]);

        // 2. Baru looping karyawan dummy (pake format 628 juga)
        for($i = 1; $i <= 20; $i++){
            $randomPhone = '628' . $faker->randomNumber(5, true) . $faker->randomNumber(4, true);

            Employee::create([
                'division_id'=> $divisions->random()->id,
                'name' => $faker->name,
                'phone' => $randomPhone,
                'is_active' => true,
            ]);
        }
    }
}
