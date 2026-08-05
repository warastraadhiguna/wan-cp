<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->json('lab_state')->nullable()->after('level_states');
        });
    }

    public function down(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->dropColumn('lab_state');
        });
    }
};
