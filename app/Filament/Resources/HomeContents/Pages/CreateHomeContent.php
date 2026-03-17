<?php

namespace App\Filament\Resources\HomeContents\Pages;

use App\Filament\Resources\HomeContents\HomeContentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;

class CreateHomeContent extends CreateRecord
{
    protected static string $resource = HomeContentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->columnSpanFull()
            ->maxWidth(Width::Full);
    }
}
