<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'message' => $request->string('message')->toString(),
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500)->toString(),
            'submitted_at' => now(),
        ]);

        return redirect()
            ->to(route('home').'#contact')
            ->with('contact_message_status', 'sent');
    }
}
