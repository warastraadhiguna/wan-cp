<?php

namespace Database\Seeders;

use App\Models\PortfolioItem;
use App\Support\SiteContentDefaults;
use Illuminate\Database\Seeder;

class PortfolioItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SiteContentDefaults::portfolioItems() as $portfolioItem) {
            PortfolioItem::query()->updateOrCreate(
                ['sort_order' => $portfolioItem['sort_order']],
                $portfolioItem,
            );
        }
    }
}
