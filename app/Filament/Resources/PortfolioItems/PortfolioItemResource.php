<?php

namespace App\Filament\Resources\PortfolioItems;

use App\Filament\Resources\PortfolioItems\Pages\CreatePortfolioItem;
use App\Filament\Resources\PortfolioItems\Pages\EditPortfolioItem;
use App\Filament\Resources\PortfolioItems\Pages\ListPortfolioItems;
use App\Models\PortfolioItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfolioItemResource extends Resource
{
    protected static ?string $model = PortfolioItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Portofolio';

    protected static ?string $pluralModelLabel = 'Portofolio';

    protected static ?string $modelLabel = 'Portofolio';

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
                TextInput::make('badge_id')
                    ->label('Badge (ID)')
                    ->required(),
                TextInput::make('badge_en')
                    ->label('Badge (EN)')
                    ->required(),
                TextInput::make('title_id')
                    ->label('Judul (ID)')
                    ->required(),
                TextInput::make('title_en')
                    ->label('Judul (EN)')
                    ->required(),
                TextInput::make('subtitle_id')
                    ->label('Subjudul (ID)')
                    ->required(),
                TextInput::make('subtitle_en')
                    ->label('Subjudul (EN)')
                    ->required(),
                FileUpload::make('image_url')
                    ->label('Gambar')
                    ->disk('public')
                    ->directory('portfolio-items')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->image()
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->formatStateUsing(fn ($state) => filled($state) && str_starts_with($state, 'http') ? null : $state)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('project_url')
                    ->label('URL Proyek')
                    ->url()
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_src')
                    ->label('Gambar')
                    ->square(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                TextColumn::make('title_id')
                    ->label('Judul ID')
                    ->searchable(),
                TextColumn::make('title_en')
                    ->label('Judul EN')
                    ->searchable(),
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
            'index' => ListPortfolioItems::route('/'),
            'create' => CreatePortfolioItem::route('/create'),
            'edit' => EditPortfolioItem::route('/{record}/edit'),
        ];
    }
}
