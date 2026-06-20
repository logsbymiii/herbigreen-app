<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Division;
use App\Models\Employee;

class CleanDivisions extends Command
{
    protected $signature = 'clean:divisions';
    protected $description = 'Clean up duplicate divisions and set exactly 11 final divisions';

    public function handle()
    {
        $this->info('Starting division cleanup...');

        $map = [
            'Editor' => 'Editor Konten',
            'CRM' => 'Admin CRM',
            'Packing' => 'Tim Packing',
            'Head & Admin Toko' => 'Admin Toko'
        ];

        foreach ($map as $oldName => $newName) {
            $oldDiv = Division::where('name', $oldName)->first();
            $newDiv = Division::where('name', $newName)->first();
            
            if (!$newDiv) {
                $newDiv = new Division();
                $newDiv->name = $newName;
                $newDiv->save();
            }

            if ($oldDiv) {
                Employee::where('division_id', $oldDiv->id)->update(['division_id' => $newDiv->id]);
                $oldDiv->delete();
                $this->info("Merged $oldName into $newName");
            }
        }

        // Pastikan divisi yang mungkin kehapus ada lagi
        $required = ['HR & Brand Manager', 'Admin Sosial Media'];
        foreach ($required as $req) {
            if (!Division::where('name', $req)->exists()) {
                $div = new Division();
                $div->name = $req;
                $div->save();
                $this->info("Added missing division: $req");
            }
        }

        $this->info('Cleanup completed! You now have exactly 11 divisions.');
    }
}
