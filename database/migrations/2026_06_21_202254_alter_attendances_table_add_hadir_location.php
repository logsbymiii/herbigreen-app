<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First drop the enum constraint if possible, but SQLite doesn't support changing enum.
        // Wait, it's MySQL. We can change the column type.
        DB::statement("ALTER TABLE attendances MODIFY type ENUM('hadir', 'sakit', 'izin', 'cuti', 'alpa', 'telat', 'wfh') NOT NULL");

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('proof_path');
            $table->string('longitude')->nullable()->after('latitude');
            $table->string('location_address')->nullable()->after('longitude');
            $table->timestamp('clocked_in_at')->nullable()->after('date');
            $table->timestamp('clocked_out_at')->nullable()->after('clocked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_address', 'clocked_in_at', 'clocked_out_at']);
        });
        
        DB::statement("ALTER TABLE attendances MODIFY type ENUM('sakit', 'cuti', 'alpa') NOT NULL");
    }
};
