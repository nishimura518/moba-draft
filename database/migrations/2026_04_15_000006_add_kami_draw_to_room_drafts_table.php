<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_drafts', function (Blueprint $table) {
            $table->json('kami_draw')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('room_drafts', function (Blueprint $table) {
            $table->dropColumn('kami_draw');
        });
    }
};

