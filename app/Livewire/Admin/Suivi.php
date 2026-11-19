<?php

namespace App\Livewire\Admin;

use App\Enums\EtatScrutin;
use App\Models\Journal;
use App\Models\Scrutin;
use App\Services\CycleScrutins;
use App\Services\Depouillement;
use App\Services\Urne;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Suivi d'un scrutin par l'administration : programmation, participation en direct,
// dépouillement, intégrité de l'urne, publication et exports.
#[Layout('layouts::app')]
class Suivi extends Component
{
    public Scrutin $scrutin;

    public function programmer(CycleScrutins $cycle): void
    {
        $cycle->programmer($this->scrutin);
        session()->flash('succes', 'Scrutin programmé : il est annoncé aux électeurs, les candidatures sont ouvertes.');
    }

    public function publier(CycleScrutins $cycle): void
    {
        $cycle->publier($this->scrutin);
        session()->flash('succes', 'Résultats publiés et électeurs avertis.');
    }

    public function supprimer()
    {
        abort_unless($this->scrutin->estModifiable(), 403);
        Journal::noter('scrutin_supprime', ['scrutin' => $this->scrutin->id, 'titre' => $this->scrutin->titre]);
        $this->scrutin->delete();
        session()->flash('succes', 'Brouillon supprimé.');

        return $this->redirect(route('admin.scrutins'), navigate: true);
    }

    public function render(Depouillement $depouillement, Urne $urne)
    {
        $etat = $this->scrutin->etat();
        $this->scrutin->load(['classes', 'groupes']);
        $depouille = in_array($etat, [EtatScrutin::Clos, EtatScrutin::Publie], true);

        return view('livewire.admin.suivi', [
            'etat' => $etat,
            'candidatures' => $this->scrutin->candidatures()->with('user.classe')->orderByRaw("case statut when 'validee' then 0 when 'en_attente' then 1 else 2 end")->get(),
            'r' => $depouille ? $depouillement->resultats($this->scrutin) : null,
            // l'intégrité se vérifie dès qu'il y a des bulletins ; les voix restent cachées pendant le vote
            // liste d'émargement triée par nom (jamais dans l'ordre des votes)
            'emargements' => $this->scrutin->purge_at ? collect() : $this->scrutin->emargements()->join('users', 'users.id', '=', 'emargements.user_id')
                ->leftJoin('classes', 'classes.id', '=', 'users.classe_id')->orderBy('users.nom')->orderBy('users.prenom')
                ->get(['users.matricule', 'users.nom', 'users.prenom', 'classes.nom as classe', 'emargements.date_vote']),
            'integrite' => $etat !== EtatScrutin::Brouillon ? ['probleme' => $urne->verifierIntegrite($this->scrutin), 'bulletins' => $this->scrutin->bulletins()->count()] : null,
        ])->title($this->scrutin->titre);
    }
}
