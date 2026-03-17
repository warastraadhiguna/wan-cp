<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Pesan Masuk';

    protected static ?string $pluralModelLabel = 'Pesan Masuk';

    protected static ?string $modelLabel = 'Pesan Masuk';

    protected static string|\UnitEnum|null $navigationGroup = 'Kontak';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_read')
                    ->label('Dibaca')
                    ->boolean()
                    ->getStateUsing(fn (ContactMessage $record): bool => $record->read_at !== null),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('read_status')
                    ->label('Waktu Dibaca')
                    ->state(fn (ContactMessage $record) => $record->read_at)
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum dibaca')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('markAsRead')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ContactMessage $record): bool => $record->read_at === null)
                    ->action(fn (ContactMessage $record) => $record->markAsRead()),
                Action::make('markAsUnread')
                    ->label('Tandai Belum Dibaca')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (ContactMessage $record): bool => $record->read_at !== null)
                    ->action(fn (ContactMessage $record) => $record->markAsUnread()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markSelectedAsRead')
                        ->label('Tandai Terpilih Dibaca')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each->markAsRead();
                        }),
                    BulkAction::make('markSelectedAsUnread')
                        ->label('Tandai Terpilih Belum Dibaca')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each->markAsUnread();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
        ];
    }
}
