<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\KoKiProgresses\KoKiProgressResource;
use App\Models\ContactMessage;
use App\Models\KoKiProgress;
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

    public function test_admin_sidebar_can_be_collapsed_on_desktop(): void
    {
        $this->assertTrue(filament()->getPanel('admin')->isSidebarCollapsibleOnDesktop());
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

    public function test_authenticated_admin_can_manage_wan_ko_ki_progress(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        KoKiProgress::query()->create([
            'student_name' => 'Naya',
            'grade' => 4,
            'last_phase' => 'activity',
            'current_level' => 1,
            'approved_levels' => [],
            'completed_levels' => [],
            'started_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(KoKiProgressResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Naya')
            ->assertSee('Menunggu izin Misi 1')
            ->assertSee('Izinkan Misi 1');
    }

    public function test_non_admin_cannot_access_wan_ko_ki_management(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(KoKiProgressResource::getUrl('index'))
            ->assertForbidden();
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
