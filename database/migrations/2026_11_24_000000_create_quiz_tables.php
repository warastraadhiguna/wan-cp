<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_meetings', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->string('title', 120);
            $table->unsignedTinyInteger('active_slot')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('quiz_banks', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('quiz_meeting_id')->constrained()->cascadeOnDelete();
            $table->json('questions');
            $table->unsignedSmallInteger('seconds_per_question');
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('quiz_bank_id')->constrained()->cascadeOnDelete();
            $table->string('player_key', 64);
            $table->string('nickname', 30);
            $table->json('answers');
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedInteger('correct_time_ms')->default(0);
            $table->unsignedBigInteger('question_started_ms')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_bank_id', 'player_key']);
            $table->index(['quiz_bank_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_banks');
        Schema::dropIfExists('quiz_meetings');
    }
};
