<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->columnSpanFull()
            ->maxWidth(Width::Full);
    }
}
