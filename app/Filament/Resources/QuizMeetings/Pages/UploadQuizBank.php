<?php

namespace App\Filament\Resources\QuizMeetings\Pages;

use App\Filament\Resources\QuizMeetings\QuizMeetingResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class UploadQuizBank extends Page
{
    use InteractsWithRecord;

    protected static string $resource = QuizMeetingResource::class;

    protected static ?string $title = 'Unggah bank soal TXT';

    protected string $view = 'filament.quiz.upload';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }
}
