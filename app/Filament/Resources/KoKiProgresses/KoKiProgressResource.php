<?php

namespace App\Filament\Resources\KoKiProgresses;

use App\Filament\Resources\KoKiProgresses\Pages\ListKoKiProgresses;
use App\Models\KoKiProgress;
use App\Services\KoKiProgressService;
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
                    ->label('Misi aktif')
                    ->formatStateUsing(fn (int $state): string => 'Misi '.$state),
                TextColumn::make('completed_count')
                    ->label('Selesai')
                    ->state(fn (KoKiProgress $record): string => count($record->completed_levels ?? []).' dari '.KoKiProgressService::MISSION_COUNT),
                TextColumn::make('access_status')
                    ->label('Izin berikutnya')
                    ->badge()
                    ->state(fn (KoKiProgress $record): string => static::accessLabel($record))
                    ->color(fn (KoKiProgress $record): string => static::canApprove($record) ? 'warning' : 'success'),
                TextColumn::make('attempt_count')
                    ->label('Percobaan')
                    ->numeric(),
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
                    ->label(fn (KoKiProgress $record): string => 'Izinkan Misi '.static::nextLevel($record))
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (KoKiProgress $record): bool => static::canApprove($record))
                    ->requiresConfirmation()
                    ->modalDescription('Siswa dapat masuk ke misi berikutnya segera setelah izin diberikan.')
                    ->action(function (KoKiProgress $record): void {
                        app(KoKiProgressService::class)->approveNextLevel($record, static::nextLevel($record));

                        Notification::make()
                            ->title('Misi berikutnya sudah diizinkan')
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
                        app(KoKiProgressService::class)->revokeLatestLevel($record);

                        Notification::make()
                            ->title('Izin misi dibatalkan')
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Izinkan misi berikutnya')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(KoKiProgressService::class);
                            $approved = 0;

                            foreach ($records as $record) {
                                if (static::canApprove($record)) {
                                    $service->approveNextLevel($record, static::nextLevel($record));
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title($approved.' siswa mendapat izin misi berikutnya')
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
        return app(KoKiProgressService::class)->approvedLevels($record);
    }

    private static function nextLevel(KoKiProgress $record): int
    {
        $approvedLevels = static::approvedLevels($record);

        return $approvedLevels === [] ? 1 : min(KoKiProgressService::MISSION_COUNT, max($approvedLevels) + 1);
    }

    private static function canApprove(KoKiProgress $record): bool
    {
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
        $approvedLevels = static::approvedLevels($record);

        if ($approvedLevels === []) {
            return false;
        }

        $latestLevel = max($approvedLevels);

        return ! in_array($latestLevel, $record->completed_levels ?? [], true);
    }

    private static function accessLabel(KoKiProgress $record): string
    {
        if ($record->completed_at !== null) {
            return 'Pertemuan selesai';
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
}
