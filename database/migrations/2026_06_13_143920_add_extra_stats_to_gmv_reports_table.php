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
        Schema::table('gmv_reports', function (Blueprint $table) {
            $table->integer('order_count')->nullable()->after('gmv_amount');
            $table->integer('product_sold')->nullable()->after('order_count');
            $table->integer('viewers_count')->nullable()->after('product_sold');
            $table->integer('highest_viewers')->nullable()->after('viewers_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gmv_reports', function (Blueprint $table) {
            $table->dropColumn(['order_count', 'product_sold', 'viewers_count', 'highest_viewers']);
        });
    }
};
