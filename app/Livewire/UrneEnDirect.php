<?php

namespace App\Livewire;

use App\Models\Scrutin;
use Livewire\Component;

// Accueil : l'urne du vote en cours, en direct. Les derniers bulletins scellés (numéro et
// empreinte) et la participation, actualisés toutes les 15 secondes. Jamais les voix.
class UrneEnDirect extends Component
{
    public function render()
    {
        // participation en temps réel sur la page publique seulement si l'administration l'a choisi (« résultats publics »)
        $scrutin = Scrutin::whereNotNull('programme_at')->whereNull('publie_at')->where('resultats_publics', true)
            ->where('ouverture_at', '<=', now())->where('cloture_at', '>', now())
            ->with(['classes', 'groupes'])->orderBy('cloture_at')->first();

        return view('livewire.urne-en-direct', [
            'scrutin' => $scrutin,
            'derniers' => $scrutin?->bulletins()->orderByDesc('rang')->limit(1)->get(['rang']) ?? collect(),
        ]);
    }
}
