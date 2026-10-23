<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic, plainly rendered platform email.
 *
 * Only non-sensitive template data is passed in: never passwords, tokens, session
 * identifiers, API keys or full itineraries.
 */
class PlatformMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $data
     */
    public function __construct(
        public readonly string $template,
        public readonly array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectFor($this->template));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform',
            with: ['template' => $this->template, 'data' => $this->data],
        );
    }

    private function subjectFor(string $template): string
    {
        return match ($template) {
            'welcome' => 'Welcome to Exam Travel Assist',
            'deletion_requested' => 'We received your account deletion request',
            'account_deleted' => 'Your Exam Travel Assist account has been deleted',
            'report_resolved' => 'Your community report has been reviewed',
            default => 'Exam Travel Assist notification',
        };
    }
}
