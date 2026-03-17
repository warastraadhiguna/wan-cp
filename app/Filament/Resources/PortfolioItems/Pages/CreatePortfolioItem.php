<?php

namespace App\Filament\Resources\PortfolioItems\Pages;

use App\Filament\Resources\PortfolioItems\PortfolioItemResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;

class CreatePortfolioItem extends CreateRecord
{
    protected static string $resource = PortfolioItemResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->columnSpanFull()
            ->maxWidth(Width::Full);
    }
}
