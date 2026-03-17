<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Layanan';

    protected static ?string $pluralModelLabel = 'Layanan';

    protected static ?string $modelLabel = 'Layanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                TextInput::make('icon_class')
                    ->label('Class Icon Bootstrap')
                    ->placeholder('bi bi-lightbulb')
                    ->required(),
                TextInput::make('icon_color')
                    ->label('Warna Icon')
                    ->placeholder('#3b82f6')
                    ->required(),
                TextInput::make('title_id')
                    ->label('Judul (ID)')
                    ->required(),
                TextInput::make('title_en')
                    ->label('Judul (EN)')
                    ->required(),
                Textarea::make('description_id')
                    ->label('Deskripsi (ID)')
                    ->rows(4)
                    ->required(),
                Textarea::make('description_en')
                    ->label('Deskripsi (EN)')
                    ->rows(4)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                TextColumn::make('title_id')
                    ->label('Judul ID')
                    ->searchable(),
                TextColumn::make('title_en')
                    ->label('Judul EN')
                    ->searchable(),
                TextColumn::make('icon_class')
                    ->label('Icon')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
