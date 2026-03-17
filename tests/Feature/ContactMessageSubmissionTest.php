<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ContactMessageSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_contact_message_is_stored(): void
    {
        $response = $this->post(route('contact-messages.store'), [
            'name' => '  <b>Angling Kusumandhita</b>  ',
            'email' => 'INFO@EXAMPLE.COM',
            'message' => '   <script>alert(1)</script>Kami ingin membuat sistem dashboard baru untuk perusahaan.   ',
            'website' => '',
            'form_token' => Crypt::encryptString((string) now()->subSeconds(5)->timestamp),
        ]);

        $response->assertRedirect(route('home').'#contact');
        $response->assertSessionHas('contact_message_status', 'sent');

        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Angling Kusumandhita',
            'email' => 'info@example.com',
            'message' => 'alert(1)Kami ingin membuat sistem dashboard baru untuk perusahaan.',
        ]);
    }

    public function test_honeypot_field_blocks_submission(): void
    {
        $response = $this->from(route('home').'#contact')->post(route('contact-messages.store'), [
            'name' => 'Angling Kusumandhita',
            'email' => 'info@example.com',
            'message' => 'Kami ingin membuat sistem dashboard baru untuk perusahaan.',
            'website' => 'https://spam-bot.invalid',
            'form_token' => Crypt::encryptString((string) now()->subSeconds(5)->timestamp),
        ]);

        $response->assertRedirect(route('home').'#contact');
        $response->assertSessionHasErrors(['website']);
        $this->assertDatabaseCount('contact_messages', 0);
    }
}
