<?php

use App\Models\RoomPlayer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('seat_number')->nullable()->after('room_id');
            $table->string('team', 5)->nullable()->after('seat_number'); // left/right
            $table->boolean('is_leader')->default(false)->after('team');
            $table->unique(['room_id', 'seat_number']);
            $table->index(['room_id', 'team']);
        });

        // Backfill existing rows per room (ordered by joined_at then id)
        $roomIds = DB::table('room_players')->distinct()->pluck('room_id');
        foreach ($roomIds as $roomId) {
            $players = RoomPlayer::query()
                ->where('room_id', $roomId)
                ->orderBy('joined_at')
                ->orderBy('id')
                ->get();

            $seat = 1;
            foreach ($players as $player) {
                $team = $seat % 2 === 1 ? 'left' : 'right';
                $isLeader = $seat <= 2;

                $player->seat_number = $seat;
                $player->team = $team;
                $player->is_leader = $isLeader;
                $player->display_name = "ユーザー{$seat}";
                $player->save();

                $seat++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'team']);
            $table->dropUnique(['room_id', 'seat_number']);
            $table->dropColumn(['seat_number', 'team', 'is_leader']);
        });
    }
};

