<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $token,
        public readonly string $role,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', config('app.name'));
        return new Envelope(subject: "You've been invited to join {$companyName}");
    }

    public function content(): Content
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->user->email,
        ], false));

        return new Content(
            view: 'emails.user_invitation',
            with: [
                'user'        => $this->user,
                'role'        => $this->role,
                'resetUrl'    => $resetUrl,
                'companyName' => Setting::get('company_name', config('app.name')),
                'companyEmail'=> Setting::get('company_email', ''),
            ],
        );
    }
}
