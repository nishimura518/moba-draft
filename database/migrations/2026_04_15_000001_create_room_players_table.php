<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('session_id');
            $table->string('display_name', 30);
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['room_id', 'session_id']);
            $table->index(['room_id', 'joined_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_players');
    }
};

