<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ko_ki_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_name', 40);
            $table->unsignedTinyInteger('grade');
            $table->string('last_phase', 50)->default('opening');
            $table->json('initial_answer')->nullable();
            $table->json('completed_levels')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->json('last_program')->nullable();
            $table->text('exit_ticket')->nullable();
            $table->text('reflection')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['grade', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ko_ki_progresses');
    }
};
