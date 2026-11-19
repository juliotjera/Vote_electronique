<?php

namespace App\Livewire\Admin;

use App\Models\Journal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// Traçabilité : connexions, décisions, imports, exports… jamais le contenu d'un vote
#[Layout('layouts::app')]
#[Title('Journal')]
class JournalActivite extends Component
{
    use WithPagination;

    public const LIBELLES = [
        'connexion' => 'connexion',
        'scrutin_cree' => 'scrutin créé',
        'scrutin_modifie' => 'scrutin modifié',
        'scrutin_supprime' => 'brouillon supprimé',
        'programmation' => 'scrutin programmé',
        'cloture' => 'chiffres de clôture figés',
        'publication_resultats' => 'résultats publiés',
        'purge_donnees' => 'liste d’émargement supprimée (30 jours)',
        'candidature_deposee' => 'candidature déposée',
        'candidature_retiree' => 'candidature retirée',
        'candidature_validee' => 'candidature validée',
        'candidature_refusee' => 'candidature refusée',
        'import_electeurs' => 'liste électorale importée',
        'electeur_active' => 'électeur réactivé',
        'electeur_desactive' => 'électeur désactivé',
        'rectification_donnees' => 'données personnelles corrigées',
        'export_pdf' => 'procès-verbal exporté',
        'export_csv' => 'résultats exportés (CSV)',
        'export_electeurs' => 'liste électorale exportée (CSV)',
        'export_emargement' => 'liste d’émargement exportée (CSV)',
        'preferences_modifiees' => 'préférences de rappel modifiées',
        'groupe_cree' => 'groupe créé',
        'groupe_membres' => 'membres d’un groupe importés',
        'groupe_supprime' => 'groupe supprimé',
        // tentatives refusées (cahier des charges : journalisation des tentatives invalides)
        'connexion_refusee' => 'connexion refusée',
        'connexion_bloquee' => 'demandes de code bloquées',
        'code_refuse' => 'code de connexion refusé',
        'acces_refuse' => 'accès à un scrutin refusé',
        'vote_refuse' => 'vote refusé',
    ];

    public const REFUS = ['connexion_refusee', 'connexion_bloquee', 'code_refuse', 'acces_refuse', 'vote_refuse'];

    public const MOTIFS = [
        'identifiants_inconnus' => 'matricule ou e-mail inconnu',
        'compte_desactive' => 'compte désactivé',
        'trop_de_demandes' => 'trop de demandes',
        'expire' => 'code expiré',
        'trop_d_essais' => 'trop d’essais',
        'incorrect' => 'code incorrect',
    ];

    #[Url]
    public string $filtre = 'tout';

    public function updatedFiltre(): void
    {
        $this->resetPage();
    }

    public static function libelle(string $action): string
    {
        return self::LIBELLES[$action] ?? str_replace('_', ' ', $action);
    }

    public function render()
    {
        return view('livewire.admin.journal', [
            'lignes' => Journal::with('user')->when($this->filtre === 'refus', fn ($q) => $q->whereIn('action', self::REFUS))
                ->latest('id')->paginate(30),
            'refus' => Journal::whereIn('action', self::REFUS)->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }
}
