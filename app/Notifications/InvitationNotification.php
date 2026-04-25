<?php

namespace App\Notifications;

use App\Models\InvitationToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly InvitationToken $invitationToken) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $token = $this->invitationToken->loadMissing(['agent', 'position.campaign']);
        $url = route('candidate.portal', ['token' => $token->token]);

        return (new MailMessage)
            ->subject('Invitation à candidater — Recrutement MSHP')
            ->greeting("Bonjour {$token->agent->first_name} {$token->agent->last_name},")
            ->line("Vous êtes invité(e) à soumettre un dossier de candidature pour le poste : **{$token->position->title}**.")
            ->line("Le lien ci-dessous est personnel et valide jusqu'au **{$token->expires_at->format('d/m/Y à H:i')}**.")
            ->action('Accéder à mon dossier', $url)
            ->line('Si vous n\'êtes pas à l\'origine de cette invitation, merci d\'ignorer ce message.')
            ->salutation('Le Ministère de la Santé et de l\'Hygiène Publique');
    }
}
