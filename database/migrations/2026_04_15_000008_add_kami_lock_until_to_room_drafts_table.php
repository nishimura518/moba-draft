<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_drafts', function (Blueprint $table) {
            $table->timestamp('kami_lock_until')->nullable()->after('kami_draw');
        });
    }

    public function down(): void
    {
        Schema::table('room_drafts', function (Blueprint $table) {
            $table->dropColumn('kami_lock_until');
        });
    }
};
