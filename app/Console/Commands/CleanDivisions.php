<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Division;

class CleanDivisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-divisions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate divisions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Cleaning duplicate divisions...");

        $duplicates = DB::table('divisions')
            ->select('name', DB::raw('MIN(id) as keep_id'))
            ->groupBy('name')
            ->get();

        $deletedCount = 0;

        foreach ($duplicates as $dup) {
            $deleted = DB::table('divisions')
                ->where('name', $dup->name)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
            
            $deletedCount += $deleted;
            if ($deleted > 0) {
                $this->line("Removed $deleted duplicate(s) for division: {$dup->name}");
            }
        }

        $this->info("Done! Removed $deletedCount duplicate divisions in total.");
    }
}
