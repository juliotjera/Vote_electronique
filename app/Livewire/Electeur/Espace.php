<?php

namespace App\Livewire\Electeur;

use App\Enums\EtatScrutin;
use App\Models\Scrutin;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Espace extends Component
{
    public function render()
    {
        $user = auth()->user();
        $votes = $user->emargements()->pluck('scrutin_id')->all();
        $scrutins = Scrutin::visiblesPour($user)->with(['classes', 'groupes', 'candidatures' => fn ($q) => $q->validees()])
            ->orderBy('cloture_at')->get();

        $groupes = $scrutins->groupBy(fn (Scrutin $s) => match ($s->etat()) {
            EtatScrutin::Ouvert => in_array($s->id, $votes) ? 'votes' : 'a_voter',
            EtatScrutin::Programme => 'a_venir',
            default => 'termines',
        });

        return view('livewire.electeur.espace', [
            'user' => $user,
            'aVoter' => $groupes->get('a_voter', collect()),
            'votes' => $groupes->get('votes', collect()),
            'aVenir' => $groupes->get('a_venir', collect()),
            'termines' => $groupes->get('termines', collect())->sortByDesc('cloture_at'),
            'mesCandidatures' => $user->candidatures()->pluck('statut', 'scrutin_id'),
        ])->title(__('Mes scrutins'));
    }
}
