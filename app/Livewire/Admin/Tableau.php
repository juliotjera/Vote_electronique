<?php

namespace App\Livewire\Admin;

use App\Enums\EtatScrutin;
use App\Models\Candidature;
use App\Models\Journal;
use App\Models\Scrutin;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Tableau de bord')]
class Tableau extends Component
{
    public function render()
    {
        $scrutins = Scrutin::with(['classes', 'groupes'])->latest('ouverture_at')->get();
        $parEtat = $scrutins->groupBy(fn (Scrutin $s) => $s->etat()->value);

        return view('livewire.admin.tableau', [
            'enCours' => $parEtat->get(EtatScrutin::Ouvert->value, collect()),
            'aPublier' => $parEtat->get(EtatScrutin::Clos->value, collect()),
            'aVenir' => $parEtat->get(EtatScrutin::Programme->value, collect()),
            'brouillons' => $parEtat->get(EtatScrutin::Brouillon->value, collect()),
            'candidaturesEnAttente' => Candidature::where('statut', 'en_attente')->count(),
            'electeurs' => User::electeurs()->where('actif', true)->count(),
            'publies' => $parEtat->get(EtatScrutin::Publie->value, collect())->count(),
            'activite' => Journal::with('user')->latest('id')->limit(8)->get(),
        ]);
    }
}
