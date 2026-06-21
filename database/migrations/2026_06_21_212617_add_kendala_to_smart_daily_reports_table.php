<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('smart_daily_reports', function (Blueprint $table) {
            $table->text('kendala')->nullable()->after('ai_insight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smart_daily_reports', function (Blueprint $table) {
            $table->dropColumn('kendala');
        });
    }
};
