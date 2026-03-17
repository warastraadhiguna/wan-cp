<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_accessible(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_authenticated_admin_can_access_home_content_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/home-contents')
            ->assertOk();
    }

    public function test_authenticated_admin_can_access_contact_messages_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/contact-messages')
            ->assertOk();
    }

    public function test_contact_messages_navigation_badge_counts_only_unread_messages(): void
    {
        ContactMessage::query()->create([
            'name' => 'Unread Message',
            'email' => 'unread@example.com',
            'message' => 'Pesan ini belum dibaca.',
            'submitted_at' => now(),
        ]);

        ContactMessage::query()->create([
            'name' => 'Read Message',
            'email' => 'read@example.com',
            'message' => 'Pesan ini sudah dibaca.',
            'read_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->assertSame('1', ContactMessageResource::getNavigationBadge());
    }
}
