<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->json('exit_ticket_answers')->nullable()->after('exit_ticket');
            $table->string('reflection_choice', 100)->nullable()->after('reflection');
        });
    }

    public function down(): void
    {
        Schema::table('ko_ki_progresses', function (Blueprint $table) {
            $table->dropColumn(['exit_ticket_answers', 'reflection_choice']);
        });
    }
};
