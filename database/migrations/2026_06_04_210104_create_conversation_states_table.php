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
        Schema::create('conversation_states', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // 'telegram' or 'fonnte'
            $table->string('identifier'); // chat_id (Telegram) or phone (Fonnte)
            $table->string('current_step'); // 'awaiting_name', 'awaiting_division', etc
            $table->json('temp_data')->nullable(); // Store temporary form data
            $table->timestamps();

            $table->unique(['provider', 'identifier']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_states');
    }
};
