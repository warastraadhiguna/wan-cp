<?php

namespace Database\Seeders;

use App\Models\HomeContent;
use App\Support\SiteContentDefaults;
use Illuminate\Database\Seeder;

class HomeContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HomeContent::query()->updateOrCreate(
            ['record_key' => HomeContent::MAIN_RECORD_KEY],
            SiteContentDefaults::homeContent(),
        );
    }
}
