<?php

namespace App\Notifications;

use App\Models\Candidature;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidatureDecidee extends Notification
{
    public function __construct(public Candidature $candidature) {}

    public function via(object $notifiable): array
    {
        return $notifiable->demo ? ['database'] : ['database', 'mail'];
    }

    private function texte(): string
    {
        $titre = $this->candidature->scrutin->titre;

        return $this->candidature->statut === 'validee'
            ? "Votre candidature au scrutin « {$titre} » est validée : elle sera présentée aux électeurs."
            : "Votre candidature au scrutin « {$titre} » n'a pas été retenue. Motif : {$this->candidature->motif_rejet}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre candidature')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line($this->texte())
            ->action('Voir ma candidature', route('candidater', $this->candidature->scrutin));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => $this->candidature->statut === 'validee' ? 'Candidature validée' : 'Candidature refusée',
            'texte' => $this->texte(),
            'scrutin_id' => $this->candidature->scrutin_id,
            'statut' => $this->candidature->statut,
            'scrutin' => $this->candidature->scrutin->titre,
            'motif' => $this->candidature->motif_rejet,
        ];
    }
}
