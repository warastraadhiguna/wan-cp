<?php

namespace App\Filament\Resources\QuizMeetings\Pages;

use App\Filament\Resources\QuizMeetings\QuizMeetingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizMeetings extends ListRecords
{
    protected static string $resource = QuizMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah pertemuan'),
        ];
    }
}
