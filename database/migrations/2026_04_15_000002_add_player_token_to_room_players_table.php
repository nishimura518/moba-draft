<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->uuid('player_token')->nullable()->after('room_id');
            $table->unique(['room_id', 'player_token']);
        });
    }

    public function down(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->dropUnique(['room_id', 'player_token']);
            $table->dropColumn('player_token');
        });
    }
};

