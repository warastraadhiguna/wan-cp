<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('badge_id');
            $table->string('badge_en');
            $table->string('title_id');
            $table->string('title_en');
            $table->string('subtitle_id')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->text('image_url');
            $table->text('project_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
