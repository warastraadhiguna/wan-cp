<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_level')->default(1)->after('last_phase');
            $table->json('level_attempts')->nullable()->after('attempt_count');
            $table->json('level_programs')->nullable()->after('last_program');
            $table->json('predictions')->nullable()->after('level_programs');
            $table->json('level_states')->nullable()->after('predictions');
        });
    }

    public function down(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->dropColumn([
                'current_level',
                'level_attempts',
                'level_programs',
                'predictions',
                'level_states',
            ]);
        });
    }
};
