<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester')->default(1)->after('grade');
            $table->unsignedTinyInteger('unit')->default(1)->after('semester');
            $table->unsignedTinyInteger('meeting')->default(1)->after('unit');
            $table->index(['semester', 'unit', 'meeting'], 'ko_ki_curriculum_index');
        });
    }

    public function down(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->dropIndex('ko_ki_curriculum_index');
            $table->dropColumn(['semester', 'unit', 'meeting']);
        });
    }
};
