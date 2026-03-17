<?php

use Database\Seeders\HomeContentSeeder;
use Database\Seeders\PortfolioItemSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('home_contents') && DB::table('home_contents')->count() === 0) {
            app(HomeContentSeeder::class)->run();
        }

        if (Schema::hasTable('services') && DB::table('services')->count() === 0) {
            app(ServiceSeeder::class)->run();
        }

        if (Schema::hasTable('portfolio_items') && DB::table('portfolio_items')->count() === 0) {
            app(PortfolioItemSeeder::class)->run();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
