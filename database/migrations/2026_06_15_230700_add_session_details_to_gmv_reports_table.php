<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmv_reports', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('platform');
            $table->string('live_start')->nullable()->after('account_name');
            $table->string('live_end')->nullable()->after('live_start');
        });
    }

    public function down(): void
    {
        Schema::table('gmv_reports', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'live_start', 'live_end']);
        });
    }
};
