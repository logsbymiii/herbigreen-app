<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            'Host Live', 'Customer Service', 'Packing', 'Marketing', 'Gudang',
            'Editor', 'CRM', 'Admin Toko', 'Admin Affiliate', 
            'VideoGrapher', 'Content Creator'
        ];

        foreach($divisions as $div){
            Division::firstOrCreate(['name' => $div]);
        }
    }
}
