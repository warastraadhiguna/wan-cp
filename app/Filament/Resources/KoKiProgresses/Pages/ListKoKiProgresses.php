<?php

namespace App\Filament\Resources\KoKiProgresses\Pages;

use App\Filament\Resources\KoKiProgresses\KoKiProgressResource;
use Filament\Resources\Pages\ListRecords;

class ListKoKiProgresses extends ListRecords
{
    protected static string $resource = KoKiProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
