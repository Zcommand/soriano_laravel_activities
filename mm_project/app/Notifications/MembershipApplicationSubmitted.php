<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class MembershipApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public MembershipTransaction $application)
    {
        $this->afterCommit();
        $this->onConnection('database');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->withSymfonyMessage(function (Email $message): void {
                $message->getHeaders()->addTextHeader('X-Idempotency-Key', $this->application->submission_key);
            })
            ->subject('Membership application received')
            ->greeting('Hello '.$this->application->member_name.',')
            ->line('We received your membership application.')
            ->line('Reference number: '.$this->application->reference)
            ->line('Category: '.$this->application->category_name)
            ->line('Membership level: '.$this->application->level_name)
            ->line('Fee: PHP '.number_format($this->application->fee, 2))
            ->line('Your application will be reviewed by our staff. Payment and approval are recorded separately.')
            ->line('Keep this reference number for your records.')
            ->salutation(config('app.name'));
    }
}
