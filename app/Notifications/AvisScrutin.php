<?php

namespace App\Notifications;

use App\Models\Scrutin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Avis aux électeurs : ouverture du vote, rappel avant la clôture, résultats publiés.
// Mis en file d'attente : des centaines d'e-mails partent en arrière-plan sans bloquer le planificateur.
// (Le code de connexion, lui, part tout de suite : CodeDeConnexion n'est pas mis en file.)
class AvisScrutin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Scrutin $scrutin, public string $moment) {}

    public function via(object $notifiable): array
    {
        // l'avis reste toujours dans la cloche ; l'e-mail suit les préférences de l'électeur (« Mes données »)
        $email = $this->moment === 'rappel' ? ($notifiable->rappel ?? 'veille') !== 'aucun' : ($notifiable->avis_email ?? true);

        return ! $notifiable->demo && $email ? ['database', 'mail'] : ['database'];
    }

    private function message(): array
    {
        $fin = $this->scrutin->cloture_at->translatedFormat('l j F à H\hi');

        return match ($this->moment) {
            'ouverture' => ['Le vote est ouvert', "Le scrutin « {$this->scrutin->titre} » est ouvert jusqu'au {$fin}.", 'Voter maintenant'],
            'rappel' => ['Dernier rappel', "Le scrutin « {$this->scrutin->titre} » se termine le {$fin}. Vous n'avez pas encore voté.", 'Voter maintenant'],
            default => ['Résultats publiés', "Les résultats du scrutin « {$this->scrutin->titre} » sont disponibles.", 'Voir les résultats'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$titre, $texte, $action] = $this->message();

        return (new MailMessage)
            ->subject("{$titre} : {$this->scrutin->titre}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line($texte)
            ->action($action, route('scrutin', $this->scrutin));
    }

    public function toArray(object $notifiable): array
    {
        [$titre, $texte] = $this->message();

        // les données brutes permettent d'afficher l'avis dans la langue choisie par l'électeur
        return [
            'titre' => $titre, 'texte' => $texte, 'scrutin_id' => $this->scrutin->id, 'moment' => $this->moment,
            'scrutin' => $this->scrutin->titre, 'cloture' => $this->scrutin->cloture_at->toIso8601String(),
        ];
    }
}
