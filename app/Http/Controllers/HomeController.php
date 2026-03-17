<?php

namespace App\Http\Controllers;

use App\Models\HomeContent;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Support\SiteContentDefaults;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $content = $this->resolveHomeContent();
        $services = $this->resolveServices();
        $portfolioItems = $this->resolvePortfolioItems();
        $featuredPortfolioItems = array_slice($portfolioItems, 0, 3);

        return view('home', [
            'content' => $content,
            'services' => $services,
            'featuredPortfolioItems' => $featuredPortfolioItems,
            'portfolioItems' => $portfolioItems,
            'portfolioItemsCount' => count($portfolioItems),
            'portfolioCategoriesCount' => count(array_unique(array_filter(array_map(
                fn (array $item): ?string => $item['badge_id'] ?? $item['badge_en'] ?? null,
                $portfolioItems,
            )))),
            'translations' => $this->buildTranslations($content, $services, $portfolioItems),
        ]);
    }

    public function showUpload(string $path): StreamedResponse
    {
        abort_if(
            str_contains($path, '..') || str_starts_with($path, '/'),
            404,
        );

        abort_unless(Storage::disk('public')->exists($path), 404);

        try {
            return Storage::disk('public')->response($path);
        } catch (FileNotFoundException) {
            abort(404);
        }
    }

    protected function resolveHomeContent(): array
    {
        if (! Schema::hasTable('home_contents')) {
            return $this->withInstagramContact(SiteContentDefaults::homeContent());
        }

        return $this->withInstagramContact(
            HomeContent::query()->first()?->toArray() ?? SiteContentDefaults::homeContent(),
        );
    }

    protected function resolveServices(): array
    {
        if (! Schema::hasTable('services')) {
            return SiteContentDefaults::services();
        }

        $services = Service::query()->active()->ordered()->get()->toArray();

        return filled($services) ? $services : SiteContentDefaults::services();
    }

    protected function resolvePortfolioItems(): array
    {
        if (! Schema::hasTable('portfolio_items')) {
            return array_map(fn (array $item): array => [
                ...$item,
                'image_src' => $this->resolveImageSource($item['image_url'] ?? null),
            ], SiteContentDefaults::portfolioItems());
        }

        $portfolioItems = PortfolioItem::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (PortfolioItem $item): array => $item->toArray())
            ->all();

        return filled($portfolioItems)
            ? $portfolioItems
            : array_map(fn (array $item): array => [
                ...$item,
                'image_src' => $this->resolveImageSource($item['image_url'] ?? null),
            ], SiteContentDefaults::portfolioItems());
    }

    protected function buildTranslations(array $content, array $services, array $portfolioItems): array
    {
        $translations = [
            'id' => [
                'navHome' => $content['nav_home_id'],
                'navAbout' => $content['nav_about_id'],
                'navServices' => $content['nav_services_id'],
                'navPortfolio' => $content['nav_portfolio_id'],
                'navContact' => $content['nav_contact_id'],
                'heroBadge' => $content['hero_badge_id'],
                'heroTitle' => $content['hero_title_id'],
                'heroTitleHighlight' => $content['hero_title_highlight_id'],
                'heroDesc' => $content['hero_description_id'],
                'heroBtnServices' => $content['hero_primary_button_id'],
                'heroBtnPortfolio' => $content['hero_secondary_button_id'],
                'aboutTag' => $content['about_tag_id'],
                'aboutTitle' => $content['about_title_id'],
                'aboutDesc' => $content['about_description_id'],
                'aboutPhilTitle' => $content['about_philosophy_title_id'],
                'aboutPhilDesc' => $content['about_philosophy_description_id'],
                'aboutWaTitle' => $content['about_meaning_title_id'],
                'aboutWaDesc' => $content['about_meaning_description_id'],
                'servicesTag' => $content['services_tag_id'],
                'servicesTitle' => $content['services_title_id'],
                'servicesDesc' => $content['services_description_id'],
                'portTag' => $content['portfolio_tag_id'],
                'portTitle' => $content['portfolio_title_id'],
                'portLink' => $content['portfolio_link_label_id'],
                'projectsModalTitle' => 'Semua Proyek Kami',
                'projectsModalDesc' => 'Jelajahi rangkaian karya digital Warastra Adhiguna, dari dashboard, platform web, hingga solusi bisnis yang dirancang sesuai kebutuhan klien.',
                'projectsModalFeatured' => 'Sorotan utama',
                'projectsModalCountLabel' => 'Proyek aktif',
                'projectsModalCategoryLabel' => 'Kategori karya',
                'projectsModalShowcase' => 'Portofolio perusahaan',
                'projectsModalVisit' => 'Lihat proyek',
                'projectsModalClose' => 'Tutup',
                'contactFormSuccess' => 'Pesan Anda berhasil dikirim. Kami akan menghubungi Anda secepatnya.',
                'contactFormError' => 'Periksa kembali form di bawah ini.',
                'contactTitle' => $content['contact_title_id'],
                'contactDesc' => $content['contact_description_id'],
                'contactPhone' => $content['contact_phone_label_id'],
                'contactEmail' => $content['contact_email_label_id'],
                'contactLocation' => $content['contact_location_label_id'],
                'contactLocationVal' => $content['contact_location_value_id'],
                'formName' => $content['form_name_label_id'],
                'formNamePh' => $content['form_name_placeholder_id'],
                'formEmail' => $content['form_email_label_id'],
                'formEmailPh' => $content['form_email_placeholder_id'],
                'formMsg' => $content['form_message_label_id'],
                'formMsgPh' => $content['form_message_placeholder_id'],
                'formBtn' => $content['form_button_id'],
                'footerRights' => $content['footer_rights_id'],
                'footerTagline' => $content['footer_tagline_id'],
            ],
            'en' => [
                'navHome' => $content['nav_home_en'],
                'navAbout' => $content['nav_about_en'],
                'navServices' => $content['nav_services_en'],
                'navPortfolio' => $content['nav_portfolio_en'],
                'navContact' => $content['nav_contact_en'],
                'heroBadge' => $content['hero_badge_en'],
                'heroTitle' => $content['hero_title_en'],
                'heroTitleHighlight' => $content['hero_title_highlight_en'],
                'heroDesc' => $content['hero_description_en'],
                'heroBtnServices' => $content['hero_primary_button_en'],
                'heroBtnPortfolio' => $content['hero_secondary_button_en'],
                'aboutTag' => $content['about_tag_en'],
                'aboutTitle' => $content['about_title_en'],
                'aboutDesc' => $content['about_description_en'],
                'aboutPhilTitle' => $content['about_philosophy_title_en'],
                'aboutPhilDesc' => $content['about_philosophy_description_en'],
                'aboutWaTitle' => $content['about_meaning_title_en'],
                'aboutWaDesc' => $content['about_meaning_description_en'],
                'servicesTag' => $content['services_tag_en'],
                'servicesTitle' => $content['services_title_en'],
                'servicesDesc' => $content['services_description_en'],
                'portTag' => $content['portfolio_tag_en'],
                'portTitle' => $content['portfolio_title_en'],
                'portLink' => $content['portfolio_link_label_en'],
                'projectsModalTitle' => 'All Our Projects',
                'projectsModalDesc' => 'Explore Warastra Adhiguna digital work across dashboards, web platforms, and custom business solutions built around client needs.',
                'projectsModalFeatured' => 'Featured highlight',
                'projectsModalCountLabel' => 'Active projects',
                'projectsModalCategoryLabel' => 'Work categories',
                'projectsModalShowcase' => 'Company showcase',
                'projectsModalVisit' => 'Visit project',
                'projectsModalClose' => 'Close',
                'contactFormSuccess' => 'Your message has been sent successfully. We will contact you soon.',
                'contactFormError' => 'Please review the form below.',
                'contactTitle' => $content['contact_title_en'],
                'contactDesc' => $content['contact_description_en'],
                'contactPhone' => $content['contact_phone_label_en'],
                'contactEmail' => $content['contact_email_label_en'],
                'contactLocation' => $content['contact_location_label_en'],
                'contactLocationVal' => $content['contact_location_value_en'],
                'formName' => $content['form_name_label_en'],
                'formNamePh' => $content['form_name_placeholder_en'],
                'formEmail' => $content['form_email_label_en'],
                'formEmailPh' => $content['form_email_placeholder_en'],
                'formMsg' => $content['form_message_label_en'],
                'formMsgPh' => $content['form_message_placeholder_en'],
                'formBtn' => $content['form_button_en'],
                'footerRights' => $content['footer_rights_en'],
                'footerTagline' => $content['footer_tagline_en'],
            ],
        ];

        foreach ($services as $index => $service) {
            $key = 'srv'.($index + 1);

            $translations['id']["{$key}Title"] = $service['title_id'];
            $translations['id']["{$key}Desc"] = $service['description_id'];
            $translations['en']["{$key}Title"] = $service['title_en'];
            $translations['en']["{$key}Desc"] = $service['description_en'];
        }

        foreach ($portfolioItems as $index => $portfolioItem) {
            $key = 'port'.($index + 1);

            $translations['id']["{$key}Badge"] = $portfolioItem['badge_id'];
            $translations['id']["{$key}Title"] = $portfolioItem['title_id'];
            $translations['id']["{$key}Subtitle"] = $portfolioItem['subtitle_id'];
            $translations['en']["{$key}Badge"] = $portfolioItem['badge_en'];
            $translations['en']["{$key}Title"] = $portfolioItem['title_en'];
            $translations['en']["{$key}Subtitle"] = $portfolioItem['subtitle_en'];
        }

        return $translations;
    }

    protected function resolveImageSource(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return route('uploads.show', ['path' => $path]);
    }

    protected function withInstagramContact(array $content): array
    {
        $instagramValue = trim((string) ($content['contact_phone'] ?? ''));
        $instagramIsUrl = Str::startsWith($instagramValue, ['http://', 'https://']);
        $instagramHandle = $instagramIsUrl
            ? preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $instagramValue)
            : ltrim($instagramValue, '@');

        $instagramHandle = trim((string) $instagramHandle, "/ \t\n\r\0\x0B");

        return [
            ...$content,
            'contact_instagram_url' => $instagramIsUrl
                ? $instagramValue
                : ($instagramHandle !== '' ? 'https://instagram.com/'.$instagramHandle : '#'),
            'contact_instagram_display' => $instagramHandle !== ''
                ? '@'.$instagramHandle
                : $instagramValue,
        ];
    }
}
