<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Support\SiteContentDefaults;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SiteContentDefaults::services() as $service) {
            Service::query()->updateOrCreate(
                ['sort_order' => $service['sort_order']],
                $service,
            );
        }
    }
}
