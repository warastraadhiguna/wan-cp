<?php

namespace App\Filament\Resources\HomeContents;

use App\Filament\Resources\HomeContents\Pages\CreateHomeContent;
use App\Filament\Resources\HomeContents\Pages\EditHomeContent;
use App\Filament\Resources\HomeContents\Pages\ListHomeContents;
use App\Models\HomeContent;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeContentResource extends Resource
{
    protected static ?string $model = HomeContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Konten Beranda';

    protected static ?string $pluralModelLabel = 'Konten Beranda';

    protected static ?string $modelLabel = 'Konten Beranda';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('record_key')
                    ->default(HomeContent::MAIN_RECORD_KEY)
                    ->required(),
                Tabs::make('Konten Beranda')
                    ->tabs([
                        Tab::make('Navigasi')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('nav_home_id')->label('Menu Beranda (ID)')->required(),
                                TextInput::make('nav_home_en')->label('Menu Home (EN)')->required(),
                                TextInput::make('nav_about_id')->label('Menu Tentang (ID)')->required(),
                                TextInput::make('nav_about_en')->label('Menu About (EN)')->required(),
                                TextInput::make('nav_services_id')->label('Menu Layanan (ID)')->required(),
                                TextInput::make('nav_services_en')->label('Menu Services (EN)')->required(),
                                TextInput::make('nav_portfolio_id')->label('Menu Portofolio (ID)')->required(),
                                TextInput::make('nav_portfolio_en')->label('Menu Portfolio (EN)')->required(),
                                TextInput::make('nav_contact_id')->label('Menu Kontak (ID)')->required(),
                                TextInput::make('nav_contact_en')->label('Menu Contact (EN)')->required(),
                            ]),
                        ]),
                        Tab::make('Hero')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('hero_badge_id')->label('Badge Hero (ID)')->required(),
                                TextInput::make('hero_badge_en')->label('Badge Hero (EN)')->required(),
                                TextInput::make('hero_title_id')->label('Judul Hero Baris 1 (ID)')->required(),
                                TextInput::make('hero_title_en')->label('Judul Hero Baris 1 (EN)')->required(),
                                TextInput::make('hero_title_highlight_id')->label('Highlight Hero (ID)')->required(),
                                TextInput::make('hero_title_highlight_en')->label('Highlight Hero (EN)')->required(),
                                TextInput::make('hero_primary_button_id')->label('Tombol Hero Utama (ID)')->required(),
                                TextInput::make('hero_primary_button_en')->label('Tombol Hero Utama (EN)')->required(),
                                TextInput::make('hero_secondary_button_id')->label('Tombol Hero Kedua (ID)')->required(),
                                TextInput::make('hero_secondary_button_en')->label('Tombol Hero Kedua (EN)')->required(),
                                TextInput::make('hero_slide_1_url')->label('URL Hero Slide 1')->url()->required(),
                                TextInput::make('hero_slide_2_url')->label('URL Hero Slide 2')->url()->required(),
                                TextInput::make('hero_slide_3_url')->label('URL Hero Slide 3')->url()->required()->columnSpanFull(),
                                Textarea::make('hero_description_id')->label('Deskripsi Hero (ID)')->rows(4)->required(),
                                Textarea::make('hero_description_en')->label('Deskripsi Hero (EN)')->rows(4)->required(),
                            ]),
                        ]),
                        Tab::make('Tentang')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('about_tag_id')->label('Tag Tentang (ID)')->required(),
                                TextInput::make('about_tag_en')->label('Tag Tentang (EN)')->required(),
                                TextInput::make('about_title_id')->label('Judul Tentang (ID)')->required(),
                                TextInput::make('about_title_en')->label('Judul Tentang (EN)')->required(),
                                TextInput::make('about_philosophy_title_id')->label('Judul Filosofi (ID)')->required(),
                                TextInput::make('about_philosophy_title_en')->label('Judul Filosofi (EN)')->required(),
                                TextInput::make('about_meaning_title_id')->label('Judul Makna (ID)')->required(),
                                TextInput::make('about_meaning_title_en')->label('Judul Makna (EN)')->required(),
                                TextInput::make('about_image_url')->label('URL Gambar Tentang')->url()->required()->columnSpanFull(),
                                Textarea::make('about_description_id')->label('Deskripsi Tentang (ID)')->rows(5)->required(),
                                Textarea::make('about_description_en')->label('Deskripsi Tentang (EN)')->rows(5)->required(),
                                Textarea::make('about_philosophy_description_id')->label('Deskripsi Filosofi (ID)')->rows(4)->required(),
                                Textarea::make('about_philosophy_description_en')->label('Deskripsi Filosofi (EN)')->rows(4)->required(),
                                Textarea::make('about_meaning_description_id')->label('Deskripsi Makna (ID)')->rows(5)->required(),
                                Textarea::make('about_meaning_description_en')->label('Deskripsi Makna (EN)')->rows(5)->required(),
                            ]),
                        ]),
                        Tab::make('Section Layanan & Portofolio')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('services_tag_id')->label('Tag Layanan (ID)')->required(),
                                TextInput::make('services_tag_en')->label('Tag Layanan (EN)')->required(),
                                TextInput::make('services_title_id')->label('Judul Layanan (ID)')->required(),
                                TextInput::make('services_title_en')->label('Judul Layanan (EN)')->required(),
                                TextInput::make('portfolio_tag_id')->label('Tag Portofolio (ID)')->required(),
                                TextInput::make('portfolio_tag_en')->label('Tag Portofolio (EN)')->required(),
                                TextInput::make('portfolio_title_id')->label('Judul Portofolio (ID)')->required(),
                                TextInput::make('portfolio_title_en')->label('Judul Portofolio (EN)')->required(),
                                TextInput::make('portfolio_link_label_id')->label('Label Link Portofolio (ID)')->required(),
                                TextInput::make('portfolio_link_label_en')->label('Label Link Portofolio (EN)')->required(),
                                TextInput::make('portfolio_link_url')->label('URL Link Portofolio')->placeholder('#')->columnSpanFull(),
                                Textarea::make('services_description_id')->label('Deskripsi Layanan (ID)')->rows(4)->required(),
                                Textarea::make('services_description_en')->label('Deskripsi Layanan (EN)')->rows(4)->required(),
                            ]),
                        ]),
                        Tab::make('Kontak & Footer')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('contact_title_id')->label('Judul Kontak (ID)')->required(),
                                TextInput::make('contact_title_en')->label('Judul Kontak (EN)')->required(),
                                TextInput::make('contact_phone_label_id')->label('Label Instagram (ID)')->required(),
                                TextInput::make('contact_phone_label_en')->label('Label Instagram (EN)')->required(),
                                TextInput::make('contact_email_label_id')->label('Label Email (ID)')->required(),
                                TextInput::make('contact_email_label_en')->label('Label Email (EN)')->required(),
                                TextInput::make('contact_location_label_id')->label('Label Lokasi (ID)')->required(),
                                TextInput::make('contact_location_label_en')->label('Label Lokasi (EN)')->required(),
                                TextInput::make('contact_location_value_id')->label('Nilai Lokasi (ID)')->required(),
                                TextInput::make('contact_location_value_en')->label('Nilai Lokasi (EN)')->required(),
                                TextInput::make('contact_phone')->label('Username / URL Instagram')->required(),
                                TextInput::make('contact_email')->label('Email Kontak')->email()->required(),
                                TextInput::make('form_name_label_id')->label('Label Nama Form (ID)')->required(),
                                TextInput::make('form_name_label_en')->label('Label Nama Form (EN)')->required(),
                                TextInput::make('form_name_placeholder_id')->label('Placeholder Nama (ID)')->required(),
                                TextInput::make('form_name_placeholder_en')->label('Placeholder Nama (EN)')->required(),
                                TextInput::make('form_email_label_id')->label('Label Email Form (ID)')->required(),
                                TextInput::make('form_email_label_en')->label('Label Email Form (EN)')->required(),
                                TextInput::make('form_email_placeholder_id')->label('Placeholder Email (ID)')->required(),
                                TextInput::make('form_email_placeholder_en')->label('Placeholder Email (EN)')->required(),
                                TextInput::make('form_message_label_id')->label('Label Pesan (ID)')->required(),
                                TextInput::make('form_message_label_en')->label('Label Pesan (EN)')->required(),
                                TextInput::make('form_message_placeholder_id')->label('Placeholder Pesan (ID)')->required(),
                                TextInput::make('form_message_placeholder_en')->label('Placeholder Pesan (EN)')->required(),
                                TextInput::make('form_button_id')->label('Tombol Form (ID)')->required(),
                                TextInput::make('form_button_en')->label('Tombol Form (EN)')->required(),
                                TextInput::make('footer_rights_id')->label('Footer Rights (ID)')->required(),
                                TextInput::make('footer_rights_en')->label('Footer Rights (EN)')->required(),
                                TextInput::make('footer_tagline_id')->label('Tagline Footer (ID)')->required(),
                                TextInput::make('footer_tagline_en')->label('Tagline Footer (EN)')->required(),
                                Textarea::make('contact_description_id')->label('Deskripsi Kontak (ID)')->rows(4)->required(),
                                Textarea::make('contact_description_en')->label('Deskripsi Kontak (EN)')->rows(4)->required(),
                            ]),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('record_key')
                    ->label('Key')
                    ->badge(),
                TextColumn::make('contact_email')
                    ->label('Email Kontak')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return HomeContent::query()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeContents::route('/'),
            'create' => CreateHomeContent::route('/create'),
            'edit' => EditHomeContent::route('/{record}/edit'),
        ];
    }
}
