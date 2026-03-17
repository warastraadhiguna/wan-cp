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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('icon_class');
            $table->string('icon_color')->default('#3b82f6');
            $table->string('title_id');
            $table->string('title_en');
            $table->text('description_id');
            $table->text('description_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
