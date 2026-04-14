<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('max_players');
            $table->string('creator_ip', 45)->nullable()->after('expires_at');
            $table->timestamp('last_activity_at')->nullable()->after('creator_ip');
        });

        $hours = (int) env('MOBA_ROOM_SLIDING_TTL_HOURS', 72);
        $until = now()->addHours(max(1, $hours));
        DB::table('rooms')->whereNull('expires_at')->update([
            'expires_at' => $until,
            'last_activity_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'creator_ip', 'last_activity_at']);
        });
    }
};
