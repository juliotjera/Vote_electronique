<?php

namespace App\Notifications;

use App\Services\ConnexionParCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CodeDeConnexion extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre code de connexion : {$this->code}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Voici votre code pour vous connecter à l'espace de vote :")
            ->line("**{$this->code}**")
            ->line('Il est valable '.ConnexionParCode::DUREE_MINUTES.' minutes et ne sert qu’une fois.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.');
    }
}
