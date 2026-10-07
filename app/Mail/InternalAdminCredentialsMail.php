<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class InternalAdminCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $credentials
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CarelioEMR Internal Admin Credentials - ' . $this->subscription->doctor_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.internal_admin_credentials',
            with: [
                'subscription' => $this->subscription,
                'credentials' => $this->credentials,
                'siteUrl' => $this->subscription->getCanonicalSiteUrl(),
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                'X-Priority' => '1',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
