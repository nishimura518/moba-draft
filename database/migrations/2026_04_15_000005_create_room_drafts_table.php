<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('status', 20)->default('lobby'); // lobby|running|completed

            // Ban/Pick state (minimal v1)
            $table->json('left_bans')->nullable();
            $table->json('right_bans')->nullable();
            $table->json('left_picks')->nullable();
            $table->json('right_picks')->nullable();

            $table->unsignedTinyInteger('turn_index')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_drafts');
    }
};

