<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\KoKiProgresses\KoKiProgressResource;
use App\Filament\Resources\KoKiProgresses\Pages\ListKoKiProgresses;
use App\Models\ContactMessage;
use App\Models\KoKiProgress;
use App\Models\User;
use App\Services\KoKiAdventureService;
use App\Services\KoKiCreatorService;
use App\Services\KoKiDetectiveProgressService;
use App\Services\KoKiRescueProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_accessible(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_uses_top_navigation(): void
    {
        $panel = filament()->getPanel('admin');

        $this->assertTrue($panel->hasTopNavigation());
        $this->assertFalse($panel->isSidebarCollapsibleOnDesktop());
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
            'semester' => 1,
            'unit' => 1,
            'meeting' => 1,
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
            ->assertSee('S1 · U1 · P1')
            ->assertSee('Menunggu izin Misi 1')
            ->assertSee('Izinkan Misi 1')
            ->assertSee('Hapus data');
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

    public function test_admin_can_approve_the_next_factory_rush_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Bima',
            'grade' => 4,
            'semester' => 1,
            'unit' => 1,
            'meeting' => 2,
            'last_phase' => 'factory_map',
            'current_level' => 1,
            'completed_levels' => [1],
            'ipo_state' => [
                'adventure_version' => KoKiAdventureService::ADVENTURE_VERSION,
                'intro_seen' => true,
                'unlocked_levels' => [1, 2],
                'approved_levels' => [1],
                'completed_levels' => [1],
                'training_replay_seen' => true,
            ],
            'started_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListKoKiProgresses::class)
            ->filterTable('meeting', 2)
            ->assertCanSeeTableRecords([$progress])
            ->assertSee('Bima')
            ->assertSee('Menunggu izin Level 2')
            ->assertSee('Izinkan Level 2')
            ->callTableAction('approveNextLevel', $progress);

        $this->assertSame([1, 2], $progress->refresh()->ipo_state['approved_levels']);
    }

    public function test_admin_can_allow_creator_lab_playtest_after_setup(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Lala',
            'grade' => 4,
            'semester' => 1,
            'unit' => 1,
            'meeting' => 5,
            'last_phase' => 'playtest',
            'current_level' => 4,
            'approved_levels' => [],
            'completed_levels' => [1, 2, 3, 4],
            'level_states' => [
                '_creator' => [
                    ...KoKiCreatorService::DEFAULTS,
                    'stage' => 4,
                    'tested_win' => true,
                ],
            ],
            'started_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListKoKiProgresses::class)
            ->filterTable('meeting', 5)
            ->assertCanSeeTableRecords([$progress])
            ->assertSee('Lala')
            ->assertSee('Menunggu izin Playtest')
            ->assertSee('Izinkan Playtest')
            ->callTableAction('approveNextLevel', $progress);

        $this->assertSame([KoKiCreatorService::PLAYTEST_APPROVAL], $progress->refresh()->approved_levels);
    }

    public function test_admin_can_approve_the_next_rescue_mission_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Naya', 'grade' => 4, 'semester' => 1, 'unit' => 1, 'meeting' => 3,
            'last_phase' => 'rescue_map', 'current_level' => 1, 'approved_levels' => [1],
            'completed_levels' => [1], 'level_states' => ['_rescue' => ['intro_seen' => true]],
            'started_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListKoKiProgresses::class)
            ->filterTable('meeting', 3)
            ->assertCanSeeTableRecords([$progress])
            ->assertSee('Menunggu izin Level 2')
            ->assertSee('Izinkan Level 2')
            ->callTableAction('approveNextLevel', $progress);

        $this->assertSame([1, 2], app(KoKiRescueProgressService::class)->state($progress->refresh())['approved_levels']);
    }

    public function test_admin_can_monitor_bug_detective_collection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Dita', 'grade' => 5, 'semester' => 1, 'unit' => 1, 'meeting' => 4,
            'last_phase' => 'detective_map', 'current_level' => 3, 'completed_levels' => [1, 2, 3],
            'level_states' => ['_detective' => ['intro_seen' => true]], 'started_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListKoKiProgresses::class)
            ->filterTable('meeting', 4)
            ->assertCanSeeTableRecords([$progress])
            ->assertSee('Dita')
            ->assertSee('Menunggu izin Case 4')
            ->assertSee('Izinkan Case 4')
            ->assertSee('Case 4')
            ->callTableAction('approveNextLevel', $progress);

        $this->assertSame(3, app(KoKiDetectiveProgressService::class)->state($progress)['collection_count']);
        $this->assertSame([1, 2, 3, 4], $progress->refresh()->approved_levels);
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
