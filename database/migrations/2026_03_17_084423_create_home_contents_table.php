<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('home_contents', function (Blueprint $table) {
            $table->id();
            $table->string('record_key')->unique();
            $table->string('nav_home_id');
            $table->string('nav_home_en');
            $table->string('nav_about_id');
            $table->string('nav_about_en');
            $table->string('nav_services_id');
            $table->string('nav_services_en');
            $table->string('nav_portfolio_id');
            $table->string('nav_portfolio_en');
            $table->string('nav_contact_id');
            $table->string('nav_contact_en');
            $table->string('hero_badge_id');
            $table->string('hero_badge_en');
            $table->string('hero_title_id');
            $table->string('hero_title_en');
            $table->string('hero_title_highlight_id');
            $table->string('hero_title_highlight_en');
            $table->text('hero_description_id');
            $table->text('hero_description_en');
            $table->string('hero_primary_button_id');
            $table->string('hero_primary_button_en');
            $table->string('hero_secondary_button_id');
            $table->string('hero_secondary_button_en');
            $table->text('hero_slide_1_url');
            $table->text('hero_slide_2_url');
            $table->text('hero_slide_3_url');
            $table->string('about_tag_id');
            $table->string('about_tag_en');
            $table->string('about_title_id');
            $table->string('about_title_en');
            $table->text('about_description_id');
            $table->text('about_description_en');
            $table->string('about_philosophy_title_id');
            $table->string('about_philosophy_title_en');
            $table->text('about_philosophy_description_id');
            $table->text('about_philosophy_description_en');
            $table->string('about_meaning_title_id');
            $table->string('about_meaning_title_en');
            $table->text('about_meaning_description_id');
            $table->text('about_meaning_description_en');
            $table->text('about_image_url');
            $table->string('services_tag_id');
            $table->string('services_tag_en');
            $table->string('services_title_id');
            $table->string('services_title_en');
            $table->text('services_description_id');
            $table->text('services_description_en');
            $table->string('portfolio_tag_id');
            $table->string('portfolio_tag_en');
            $table->string('portfolio_title_id');
            $table->string('portfolio_title_en');
            $table->string('portfolio_link_label_id');
            $table->string('portfolio_link_label_en');
            $table->text('portfolio_link_url')->nullable();
            $table->string('contact_title_id');
            $table->string('contact_title_en');
            $table->text('contact_description_id');
            $table->text('contact_description_en');
            $table->string('contact_phone_label_id');
            $table->string('contact_phone_label_en');
            $table->string('contact_email_label_id');
            $table->string('contact_email_label_en');
            $table->string('contact_location_label_id');
            $table->string('contact_location_label_en');
            $table->string('contact_location_value_id');
            $table->string('contact_location_value_en');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->string('form_name_label_id');
            $table->string('form_name_label_en');
            $table->string('form_name_placeholder_id');
            $table->string('form_name_placeholder_en');
            $table->string('form_email_label_id');
            $table->string('form_email_label_en');
            $table->string('form_email_placeholder_id');
            $table->string('form_email_placeholder_en');
            $table->string('form_message_label_id');
            $table->string('form_message_label_en');
            $table->string('form_message_placeholder_id');
            $table->string('form_message_placeholder_en');
            $table->string('form_button_id');
            $table->string('form_button_en');
            $table->string('footer_rights_id');
            $table->string('footer_rights_en');
            $table->string('footer_tagline_id');
            $table->string('footer_tagline_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_contents');
    }
};
