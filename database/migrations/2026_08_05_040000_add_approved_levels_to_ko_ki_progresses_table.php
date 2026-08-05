<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->json('approved_levels')->nullable()->after('current_level');
        });
    }

    public function down(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->dropColumn('approved_levels');
        });
    }
};
