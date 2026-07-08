<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendImportedCredentials extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $plainPassword,
        public ?string $loginUrl = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tus credenciales de acceso - Plataforma AMA')
            ->markdown('mail.imported-credentials', [
                'user' => $notifiable,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $this->loginUrl ?? route('login'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plain_password_sent_at' => now()->toDateTimeString(),
        ];
    }
}
