<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\GmvReport;
use App\Models\GmvReports;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DummyTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $employees = Employee::pluck('id')->toArray();

        if (empty($employees)) {
            $this->command->warn('Data karyawan masih kosong! Pastikan EmployeeSeeder sudah jalan.');
            return;
        }

        // Looping mundur dari 6 hari lalu sampai hari ini (7 Hari)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            foreach ($employees as $employeeId) {
                $chance = rand(1, 100);

                if ($chance <= 65) {
                    Report::create([
                        'employee_id' => $employeeId,
                        'type' => 'daily_report',
                        'content' => $faker->sentence(10),
                        'reported_at' => $date, // <--- UDAH AMAN
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                } elseif ($chance > 65 && $chance <= 80) {
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'type' => $faker->randomElement(['sakit', 'cuti', 'alpa']),
                        'note' => $faker->sentence(5),
                        'date' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                if (rand(1, 100) <= 20) {
                    GmvReports::create([
                        'employee_id' => $employeeId,
                        'screenshot_path' => 'dummy/screenshot.jpg', // <--- UDAH AMAN JUGA
                        'gmv_amount' => $faker->randomFloat(2, 1000000, 25000000),
                        'raw_ocr_text' => 'Teks bacaan AI abal-abal',
                        'live_date' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }
    }
}
