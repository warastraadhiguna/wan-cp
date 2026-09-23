<?php

namespace App\Filament\Resources\KoKiProgresses;

use App\Filament\Resources\KoKiProgresses\Pages\ListKoKiProgresses;
use App\Models\KoKiProgress;
use App\Services\KoKiAdventureService;
use App\Services\KoKiCreatorService;
use App\Services\KoKiDetectiveProgressService;
use App\Services\KoKiProgressService;
use App\Services\KoKiRescueProgressService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class KoKiProgressResource extends Resource
{
    protected static ?string $model = KoKiProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'WAn Ko-Ki';

    protected static ?string $pluralModelLabel = 'Progress WAn Ko-Ki';

    protected static ?string $modelLabel = 'Progress siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Koding for Kids';

    protected static ?string $slug = 'wan-ko-ki';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('student_name')
                    ->label('Nama siswa')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('grade')
                    ->label('Kelas')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => 'Kelas '.$state),
                TextColumn::make('curriculum')
                    ->label('Materi')
                    ->badge()
                    ->state(fn (KoKiProgress $record): string => "S{$record->semester} · U{$record->unit} · P{$record->meeting}"),
                TextColumn::make('current_level')
                    ->label('Aktivitas aktif')
                    ->formatStateUsing(fn (int $state, KoKiProgress $record): string => match ($record->meeting) {
                        1 => 'Misi '.$state,
                        2 => 'Level '.app(KoKiAdventureService::class)->state($record)['current_level'],
                        3 => 'Level '.app(KoKiRescueProgressService::class)->state($record)['current_level'],
                        4 => 'Case '.app(KoKiDetectiveProgressService::class)->state($record)['current_case'],
                        5 => app(KoKiCreatorService::class)->state($record)['stage'] >= 4 ? 'Playtest' : 'Setup '.app(KoKiCreatorService::class)->state($record)['stage'].'/4',
                        default => 'Level '.$state,
                    }),
                TextColumn::make('completed_count')
                    ->label('Selesai / order')
                    ->state(fn (KoKiProgress $record): string => match ($record->meeting) {
                        2 => app(KoKiAdventureService::class)->state($record)['order_count'].' order',
                        3 => count($record->completed_levels ?? []).' dari '.KoKiRescueProgressService::MISSION_COUNT,
                        4 => count($record->completed_levels ?? []).' dari '.KoKiDetectiveProgressService::CASE_COUNT,
                        5 => app(KoKiCreatorService::class)->adventureState($record)['completed'] ? '10 dari 10 level' : app(KoKiCreatorService::class)->state($record)['stage'].' dari 4 setup',
                        default => count($record->completed_levels ?? []).' dari '.KoKiProgressService::MISSION_COUNT,
                    }),
                TextColumn::make('access_status')
                    ->label('Status / izin')
                    ->badge()
                    ->state(fn (KoKiProgress $record): string => static::accessLabel($record))
                    ->color(fn (KoKiProgress $record): string => static::canApprove($record) ? 'warning' : 'success'),
                TextColumn::make('attempt_count')
                    ->label('Percobaan / poin')
                    ->formatStateUsing(fn (int $state, KoKiProgress $record): string => match ($record->meeting) {
                        2 => app(KoKiAdventureService::class)->state($record)['score'].' poin',
                        3 => app(KoKiRescueProgressService::class)->state($record)['total_stars'].' bintang',
                        4 => app(KoKiDetectiveProgressService::class)->state($record)['total_stars'].' bintang',
                        default => (string) $state,
                    }),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('semester')
                    ->label('Semester')
                    ->default(1)
                    ->options([1 => 'Semester 1', 2 => 'Semester 2']),
                SelectFilter::make('unit')
                    ->label('Unit')
                    ->default(1)
                    ->options(fn (): array => collect(range(1, 12))
                        ->mapWithKeys(fn (int $unit): array => [$unit => 'Unit '.$unit])
                        ->all()),
                SelectFilter::make('meeting')
                    ->label('Pertemuan')
                    ->default(1)
                    ->options(fn (): array => collect(range(1, 24))
                        ->mapWithKeys(fn (int $meeting): array => [$meeting => 'Pertemuan '.$meeting])
                        ->all()),
                SelectFilter::make('grade')
                    ->label('Kelas')
                    ->options(fn (): array => collect(range(1, 6))
                        ->mapWithKeys(fn (int $grade): array => [$grade => 'Kelas '.$grade])
                        ->all()),
            ])
            ->recordActions([
                Action::make('approveNextLevel')
                    ->label(fn (KoKiProgress $record): string => $record->meeting === 5
                        ? 'Izinkan '.static::creatorStepLabel($record)
                        : 'Izinkan '.static::levelLabel($record).' '.static::nextLevel($record))
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (KoKiProgress $record): bool => static::canApprove($record))
                    ->requiresConfirmation()
                    ->modalDescription('Siswa dapat masuk ke aktivitas berikutnya segera setelah izin diberikan.')
                    ->action(function (KoKiProgress $record): void {
                        static::approveNextLevel($record);

                        Notification::make()
                            ->title($record->meeting === 5 ? static::creatorStepLabel($record).' sudah diizinkan' : static::levelLabel($record).' berikutnya sudah diizinkan')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label('Hapus data')
                    ->modalHeading('Hapus progress siswa?')
                    ->modalDescription('Progress akan dihapus permanen. Browser siswa dapat memulai pertemuan ini dari awal.')
                    ->successNotificationTitle('Progress siswa berhasil dihapus'),
                Action::make('revokeLatestLevel')
                    ->label('Batalkan izin')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (KoKiProgress $record): bool => static::canRevoke($record))
                    ->requiresConfirmation()
                    ->action(function (KoKiProgress $record): void {
                        static::revokeLatestLevel($record);

                        Notification::make()
                            ->title('Izin '.strtolower(static::levelLabel($record)).' dibatalkan')
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Izinkan aktivitas berikutnya')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $approved = 0;

                            foreach ($records as $record) {
                                if (static::canApprove($record)) {
                                    static::approveNextLevel($record);
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title($approved.' siswa mendapat izin aktivitas berikutnya')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label('Hapus data terpilih')
                        ->modalHeading('Hapus progress siswa terpilih?')
                        ->modalDescription('Semua progress terpilih akan dihapus permanen. Browser siswa terkait dapat memulai dari awal.')
                        ->successNotificationTitle('Progress siswa terpilih berhasil dihapus'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = KoKiProgress::query()->whereNull('completed_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKoKiProgresses::route('/'),
        ];
    }

    private static function approvedLevels(KoKiProgress $record): array
    {
        if ($record->meeting === 5) {
            return app(KoKiCreatorService::class)->approvedSteps($record);
        }

        if ($record->meeting === 4) {
            return app(KoKiDetectiveProgressService::class)->state($record)['approved_cases'];
        }

        if ($record->meeting === 3) {
            return app(KoKiRescueProgressService::class)->state($record)['approved_levels'];
        }

        if ($record->meeting === 2) {
            return app(KoKiAdventureService::class)->approvedLevels($record);
        }

        return app(KoKiProgressService::class)->approvedLevels($record);
    }

    private static function nextLevel(KoKiProgress $record): int
    {
        if ($record->meeting === 5) {
            return app(KoKiCreatorService::class)->nextApprovableStep($record) ?? KoKiCreatorService::PLAYTEST_APPROVAL;
        }

        if ($record->meeting === 4) {
            return app(KoKiDetectiveProgressService::class)->nextApprovableCase($record) ?? KoKiDetectiveProgressService::CASE_COUNT;
        }

        if ($record->meeting === 3) {
            return app(KoKiRescueProgressService::class)->nextApprovableLevel($record) ?? KoKiRescueProgressService::MISSION_COUNT;
        }
        if ($record->meeting === 2) {
            return app(KoKiAdventureService::class)->nextApprovableLevel($record) ?? KoKiAdventureService::MISSION_COUNT;
        }

        $approvedLevels = static::approvedLevels($record);

        return $approvedLevels === [] ? 1 : min(KoKiProgressService::MISSION_COUNT, max($approvedLevels) + 1);
    }

    private static function canApprove(KoKiProgress $record): bool
    {
        if ($record->meeting === 5) {
            return app(KoKiCreatorService::class)->canApproveNextStep($record);
        }

        if ($record->meeting === 4) {
            return $record->completed_at === null
                && app(KoKiDetectiveProgressService::class)->nextApprovableCase($record) !== null;
        }

        if ($record->meeting === 3) {
            return $record->completed_at === null
                && app(KoKiRescueProgressService::class)->nextApprovableLevel($record) !== null;
        }
        if ($record->meeting === 2) {
            return $record->completed_at === null
                && app(KoKiAdventureService::class)->nextApprovableLevel($record) !== null;
        }

        if ($record->meeting !== 1) {
            return false;
        }

        $nextLevel = static::nextLevel($record);

        $approvedLevels = static::approvedLevels($record);
        $previousLevelIsComplete = $nextLevel === 1
            || in_array($nextLevel - 1, $record->completed_levels ?? [], true);

        return $record->completed_at === null
            && ($approvedLevels === [] || max($approvedLevels) < KoKiProgressService::MISSION_COUNT)
            && $record->last_phase === 'activity'
            && $previousLevelIsComplete;
    }

    private static function canRevoke(KoKiProgress $record): bool
    {
        if ($record->meeting === 5) {
            $creator = app(KoKiCreatorService::class);
            $stage = (int) $creator->state($record)['stage'];

            return collect(array_map('intval', $record->approved_levels ?? []))
                ->contains(fn (int $step): bool => $step > $stage)
                && ! $creator->adventureState($record)['completed'];
        }

        if ($record->meeting === 4) {
            $state = app(KoKiDetectiveProgressService::class)->state($record);

            return collect($state['approved_cases'])->contains(
                fn (int $case): bool => $case > 1 && ! in_array($case, $state['completed_cases'], true),
            );
        }

        if ($record->meeting === 3) {
            $state = app(KoKiRescueProgressService::class)->state($record);

            return collect($state['approved_levels'])->contains(
                fn (int $level): bool => $level > 1 && ! in_array($level, $state['completed_levels'], true),
            );
        }
        if ($record->meeting === 2) {
            $state = app(KoKiAdventureService::class)->state($record);

            return collect($state['approved_levels'])->contains(
                fn (int $level): bool => $level > 1 && ! in_array($level, $state['completed_levels'], true),
            );
        }

        if ($record->meeting !== 1) {
            return false;
        }

        $approvedLevels = static::approvedLevels($record);

        if ($approvedLevels === []) {
            return false;
        }

        $latestLevel = max($approvedLevels);

        return ! in_array($latestLevel, $record->completed_levels ?? [], true);
    }

    private static function accessLabel(KoKiProgress $record): string
    {
        if ($record->completed_at !== null
            && match ($record->meeting) {
                4 => app(KoKiDetectiveProgressService::class)->state($record)['completed'],
                5 => app(KoKiCreatorService::class)->adventureState($record)['completed'],
                default => true,
            }) {
            return 'Pertemuan selesai';
        }

        if ($record->meeting === 5) {
            $creator = app(KoKiCreatorService::class);
            $state = $creator->state($record);

            if ($creator->canApproveNextStep($record)) {
                return 'Menunggu izin '.static::creatorStepLabel($record);
            }

            if ($state['playtested']) {
                return 'Creator Adventure terbuka';
            }
            if ((int) $state['stage'] >= KoKiCreatorService::SETUP_COUNT) {
                return 'Playtest diizinkan';
            }

            return 'Setup '.(((int) $state['stage']) + 1).' diizinkan';
        }

        if ($record->meeting === 2) {
            $state = app(KoKiAdventureService::class)->state($record);

            if (static::canApprove($record)) {
                return 'Menunggu izin Level '.static::nextLevel($record);
            }

            $latestApproved = max($state['approved_levels']);

            return ! $state['intro_seen']
                ? 'Belum masuk pabrik'
                : ($latestApproved >= KoKiAdventureService::MISSION_COUNT
                    ? 'Semua level diizinkan'
                    : 'Level '.$latestApproved.' diizinkan');
        }

        if ($record->meeting === 3) {
            $state = app(KoKiRescueProgressService::class)->state($record);
            if (! $state['intro_seen']) {
                return 'Belum melihat pendahuluan';
            }
            if ($state['final_completed']) {
                return 'Menunggu evaluasi';
            }
            if (static::canApprove($record)) {
                return 'Menunggu izin Level '.static::nextLevel($record);
            }
            $latest = max($state['approved_levels']);

            return $latest >= KoKiRescueProgressService::MISSION_COUNT ? 'Semua level diizinkan' : 'Level '.$latest.' diizinkan';
        }

        if ($record->meeting === 4) {
            $state = app(KoKiDetectiveProgressService::class)->state($record);

            if (! $state['intro_seen']) {
                return 'Belum membuka CASE #001';
            }
            if ($state['final_solved']) {
                return 'Menunggu Detective Notebook';
            }

            if (static::canApprove($record)) {
                return 'Menunggu izin Case '.static::nextLevel($record);
            }

            $latest = max($state['approved_cases']);

            return $latest >= KoKiDetectiveProgressService::CASE_COUNT
                ? 'Semua case diizinkan'
                : 'Case '.$latest.' diizinkan · Koleksi '.$state['collection_count'].'/15';
        }

        if (static::canApprove($record)) {
            return 'Menunggu izin Misi '.static::nextLevel($record);
        }

        $approvedLevels = static::approvedLevels($record);

        if ($approvedLevels === []) {
            return 'Menunggu izin Misi 1';
        }

        $latestLevel = max($approvedLevels);

        return $latestLevel >= KoKiProgressService::MISSION_COUNT ? 'Semua misi terbuka' : 'Misi '.$latestLevel.' diizinkan';
    }

    private static function approveNextLevel(KoKiProgress $record): void
    {
        if ($record->meeting === 5) {
            app(KoKiCreatorService::class)->approveNextStep($record);

            return;
        }

        if ($record->meeting === 4) {
            app(KoKiDetectiveProgressService::class)->approveNextCase($record, static::nextLevel($record));

            return;
        }

        if ($record->meeting === 3) {
            app(KoKiRescueProgressService::class)->approveNextLevel($record, static::nextLevel($record));

            return;
        }
        if ($record->meeting === 2) {
            app(KoKiAdventureService::class)->approveNextLevel($record, static::nextLevel($record));

            return;
        }

        app(KoKiProgressService::class)->approveNextLevel($record, static::nextLevel($record));
    }

    private static function revokeLatestLevel(KoKiProgress $record): void
    {
        if ($record->meeting === 5) {
            app(KoKiCreatorService::class)->revokeLatestApproval($record);

            return;
        }

        if ($record->meeting === 4) {
            app(KoKiDetectiveProgressService::class)->revokeLatestCase($record);

            return;
        }

        if ($record->meeting === 3) {
            app(KoKiRescueProgressService::class)->revokeLatestLevel($record);

            return;
        }
        if ($record->meeting === 2) {
            app(KoKiAdventureService::class)->revokeLatestLevel($record);

            return;
        }

        app(KoKiProgressService::class)->revokeLatestLevel($record);
    }

    private static function levelLabel(KoKiProgress $record): string
    {
        return match ($record->meeting) {
            2, 3 => 'Level',
            4 => 'Case',
            5 => 'Playtest',
            default => 'Misi',
        };
    }

    private static function creatorStepLabel(KoKiProgress $record): string
    {
        $step = app(KoKiCreatorService::class)->nextApprovableStep($record)
            ?? min(KoKiCreatorService::PLAYTEST_APPROVAL, ((int) app(KoKiCreatorService::class)->state($record)['stage']) + 1);

        return $step >= KoKiCreatorService::PLAYTEST_APPROVAL ? 'Playtest' : 'Setup '.$step;
    }
}
