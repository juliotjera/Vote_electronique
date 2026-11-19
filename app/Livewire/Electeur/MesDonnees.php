<?php

namespace App\Livewire\Electeur;

use App\Models\Journal;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Droit d'accès et de rectification : l'électeur consulte ses données et corrige son nom
#[Layout('layouts::app')]
class MesDonnees extends Component
{
    public string $nom = '';

    public string $prenom = '';

    // rappels personnalisés : quand être prévenu avant la clôture d'un scrutin où l'on n'a pas voté
    public string $rappel = 'veille';

    public bool $avisEmail = true;

    public function mount(): void
    {
        $this->nom = auth()->user()->nom;
        $this->prenom = auth()->user()->prenom;
        $this->rappel = auth()->user()->rappel ?? 'veille';
        $this->avisEmail = (bool) (auth()->user()->avis_email ?? true);
    }

    public function enregistrerRappels(): void
    {
        $this->validate(['rappel' => 'required|in:veille,jour,aucun', 'avisEmail' => 'boolean']);
        auth()->user()->update(['rappel' => $this->rappel, 'avis_email' => $this->avisEmail]);
        Journal::noter('preferences_modifiees', ['rappel' => $this->rappel]);
        session()->flash('succes_rappels', __('Vos préférences de rappel sont enregistrées.'));
    }

    public function enregistrer(): void
    {
        $this->validate(['nom' => 'required|string|max:80', 'prenom' => 'required|string|max:80']);
        auth()->user()->update(['nom' => mb_strtoupper(trim($this->nom)), 'prenom' => trim($this->prenom)]);
        Journal::noter('rectification_donnees');
        session()->flash('succes', __('Vos informations sont corrigées.'));
    }

    public function render()
    {
        $user = auth()->user()->load('classe');

        return view('livewire.electeur.mes-donnees', [
            'user' => $user,
            'participations' => $user->emargements()->with('scrutin:id,titre')->get(),
        ])->title(__('Mes données'));
    }
}
