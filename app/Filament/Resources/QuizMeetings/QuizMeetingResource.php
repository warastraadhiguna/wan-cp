<?php

namespace App\Filament\Resources\QuizMeetings;

use App\Filament\Resources\QuizMeetings\Pages\ListQuizMeetings;
use App\Filament\Resources\QuizMeetings\Pages\UploadQuizBank;
use App\Models\QuizMeeting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizMeetingResource extends Resource
{
    protected static ?string $model = QuizMeeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $modelLabel = 'Pertemuan Kuis';

    protected static ?string $pluralModelLabel = 'Pertemuan Kuis';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Nama pertemuan')
                ->placeholder('Pertemuan 1 – Kenalan dengan Algoritma')
                ->required()
                ->maxLength(120),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('currentBank'))
            ->defaultSort('id')
            ->columns([
                TextColumn::make('title')->label('Pertemuan')->searchable(),
                TextColumn::make('status')
                    ->state(fn (QuizMeeting $record): string => $record->active_slot ? 'Aktif' : 'Nonaktif')
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'gray')
                    ->badge(),
                TextColumn::make('question_count')
                    ->label('Jumlah soal')
                    ->state(fn (QuizMeeting $record): int => count($record->currentBank?->questions ?? [])),
                TextColumn::make('currentBank.seconds_per_question')->label('Detik per soal')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('uploadBank')
                    ->label('Unggah TXT')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (QuizMeeting $record): bool => ! $record->active_slot)
                    ->url(fn (QuizMeeting $record): string => static::getUrl('upload', ['record' => $record])),
                Action::make('activate')
                    ->label('Aktifkan')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->visible(fn (QuizMeeting $record): bool => ! $record->active_slot && $record->currentBank !== null)
                    ->requiresConfirmation()
                    ->modalDescription('Kuis ini akan dibuka untuk peserta. Pertemuan yang sedang aktif akan ditutup.')
                    ->action(function (QuizMeeting $record): void {
                        DB::transaction(function () use ($record): void {
                            $meetings = QuizMeeting::query()->orderBy('id')->lockForUpdate()->get();
                            $selected = $meetings->find($record->id);
                            if (! $selected?->currentBank) {
                                throw ValidationException::withMessages(['bank' => 'Unggah bank soal terlebih dahulu.']);
                            }
                            QuizMeeting::query()->active()->whereKeyNot($selected->id)->update(['active_slot' => null]);
                            $selected->forceFill(['active_slot' => 1])->save();
                        }, 3);

                        Notification::make()->success()->title('Pertemuan dibuka')->send();
                    }),
                Action::make('deactivate')
                    ->label('Tutup')
                    ->icon(Heroicon::OutlinedStop)
                    ->visible(fn (QuizMeeting $record): bool => (bool) $record->active_slot)
                    ->requiresConfirmation()
                    ->modalDescription('Peserta akan berhenti menerima soal. Hasil yang sudah selesai tetap dapat dibuka.')
                    ->action(function (QuizMeeting $record): void {
                        QuizMeeting::query()->whereKey($record->id)->update(['active_slot' => null]);
                        Notification::make()->success()->title('Pertemuan ditutup')->send();
                    }),
                Action::make('standings')
                    ->label('Peringkat')
                    ->icon(Heroicon::OutlinedTrophy)
                    ->visible(fn (QuizMeeting $record): bool => $record->currentBank !== null)
                    ->modalHeading(fn (QuizMeeting $record): string => 'Peringkat · '.$record->title)
                    ->modalContent(fn (QuizMeeting $record) => view('filament.quiz.standings', [
                        'leaderboard' => $record->currentBank->attempts()->completed()->ranked()->limit(10)->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizMeetings::route('/'),
            'upload' => UploadQuizBank::route('/{record}/unggah'),
        ];
    }
}
