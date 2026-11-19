<?php

namespace App\Livewire\Admin;

use App\Models\Candidature;
use App\Models\Journal;
use App\Notifications\CandidatureDecidee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Candidatures')]
class Candidatures extends Component
{
    #[Url]
    public string $filtre = 'en_attente';

    public ?int $refusEnCours = null;

    public string $motif = '';

    public function valider(Candidature $candidature): void
    {
        $this->decider($candidature, 'validee');
    }

    public function demanderRefus(int $id): void
    {
        $this->refusEnCours = $id;
        $this->motif = '';
    }

    public function refuser(): void
    {
        $this->validate(['motif' => 'required|string|min:5|max:255'], [], ['motif' => 'motif']);
        $this->decider(Candidature::findOrFail($this->refusEnCours), 'rejetee', trim($this->motif));
        $this->refusEnCours = null;
    }

    private function decider(Candidature $candidature, string $statut, ?string $motif = null): void
    {
        // une décision n'est possible qu'avant l'ouverture du vote
        abort_unless($candidature->scrutin->ouverture_at->isFuture(), 403, 'Le vote est ouvert : les candidatures sont figées.');
        $candidature->update(['statut' => $statut, 'motif_rejet' => $motif, 'decide_par' => auth()->id(), 'decide_at' => now()]);
        $candidature->user->notify(new CandidatureDecidee($candidature->load('scrutin')));
        Journal::noter($statut === 'validee' ? 'candidature_validee' : 'candidature_refusee', ['scrutin' => $candidature->scrutin_id, 'candidature' => $candidature->id]);
        session()->flash('succes', $statut === 'validee' ? 'Candidature validée : le candidat est prévenu.' : 'Candidature refusée : le candidat est prévenu avec le motif.');
    }

    public function render()
    {
        return view('livewire.admin.candidatures', [
            'candidatures' => Candidature::with(['user.classe', 'scrutin'])
                ->when($this->filtre !== 'toutes', fn ($q) => $q->where('statut', $this->filtre))
                ->latest()->get(),
            'compteurs' => Candidature::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut'),
        ]);
    }
}
