<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\GmvReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User if not exists
        if (!User::where('email', 'admin@herbigreen.com')->exists()) {
            User::create([
                'name' => 'Admin Herbigreen',
                'email' => 'admin@herbigreen.com',
                'password' => Hash::make('password'),
            ]);
        }

        // 2. Create Divisions
        $divisions = [
            'Editor', 'Admin Affiliate', 'Admin CRM', 'Admin Sosial Media',
            'HR & Brand Manager', 'Tim Packing', 'Editor Konten', 
            'Head & Admin Toko', 'Admin Toko', 'Host Live', 
            'VideoGrapher', 'Content Creator'
        ];
        $divisionMap = [];
        foreach ($divisions as $divName) {
            $division = Division::firstOrCreate(['name' => $divName]);
            $divisionMap[$divName] = $division->id;
        }

        // 3. Create Employees
        $faker = \Faker\Factory::create('id_ID');
        $employees = [];

        // Buat 20 karyawan acak
        for ($i = 0; $i < 20; $i++) {
            $divName = $faker->randomElement($divisions);
            $employees[] = Employee::create([
                'division_id' => $divisionMap[$divName],
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'is_active' => true,
                'telegram_id' => null,
            ]);
        }

        // 4. Create Reports, Attendances, GMV for the last 60 days
        foreach ($employees as $employee) {
            for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
                $date = Carbon::now()->subDays($daysAgo);

                // Hari minggu libur (opsional, tapi biarin aja ada yg lapor)
                $chance = rand(1, 100);

                if ($chance <= 85) {
                    // 85% chance Hadir / Lapor
                    Report::create([
                        'employee_id' => $employee->id,
                        'type' => 'Harian',
                        'content' => $faker->sentence(10),
                        'media_path' => null,
                        'reported_at' => $date->copy()->setHour(rand(16, 22)),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    // Jika dia anak Host Live, kasih GMV
                    if ($employee->division_id == $divisionMap['Host Live']) {
                        GmvReport::create([
                            'employee_id' => $employee->id,
                            'screenshot_path' => 'dummy.png',
                            'gmv_amount' => rand(1000000, 15000000), // 1jt - 15jt
                            'raw_ocr_text' => 'Dummy OCR Text',
                            'live_date' => $date->format('Y-m-d'),
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                    }

                } elseif ($chance > 85 && $chance <= 92) {
                    // 7% chance Izin/Sakit
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'type' => $faker->randomElement(['sakit', 'cuti', 'alpa']),
                        'note' => $faker->sentence(5),
                        'proof_path' => null,
                        'date' => $date->format('Y-m-d'),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
                // Sisa 8% tidak lapor sama sekali (Belum Lapor)
            }
        }
    }
}
