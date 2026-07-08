<?php

namespace App\Notifications;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Proposal $proposal, public string $oldStatus, public ?string $comment = null) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['proposal_id' => $this->proposal->id, 'title' => $this->proposal->title, 'old_status' => $this->oldStatus, 'status' => $this->proposal->status, 'comment' => $this->comment, 'url' => route('proposals.show', $this->proposal)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Actualizacion de propuesta: '.$this->proposal->title)
            ->line('El estado de tu propuesta cambio de '.$this->oldStatus.' a '.$this->proposal->status.'.')
            ->when($this->comment, fn (MailMessage $mail) => $mail->line('Comentario: '.$this->comment))
            ->action('Ver propuesta', route('proposals.show', $this->proposal));
    }
}
