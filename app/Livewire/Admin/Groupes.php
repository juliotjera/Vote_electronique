<?php

namespace App\Livewire\Admin;

use App\Models\Groupe;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Groupes ciblés en plus des classes (cahier des charges : « classe, promotion, association ») :
// associations, clubs, comités dont les membres viennent de plusieurs classes.
#[Layout('layouts::app')]
#[Title('Groupes')]
class Groupes extends Component
{
    public string $nom = '';

    public string $description = '';

    public ?int $ouvert = null;

    // matricules collés depuis un tableur, un par ligne (ou séparés par des virgules, points-virgules, espaces)
    public string $matricules = '';

    public ?array $bilan = null;

    public function creer(): void
    {
        $this->validate([
            'nom' => ['required', 'string', 'max:80', Rule::unique('groupes', 'nom')],
            'description' => 'nullable|string|max:255',
        ], [], ['nom' => 'nom du groupe']);
        $groupe = Groupe::create(['nom' => trim($this->nom), 'description' => trim($this->description) ?: null]);
        Journal::noter('groupe_cree', ['groupe' => $groupe->id]);
        $this->reset('nom', 'description');
        $this->ouvrir($groupe->id);
    }

    public function ouvrir(int $groupe): void
    {
        $this->ouvert = $this->ouvert === $groupe ? null : $groupe;
        $this->matricules = $this->ouvert ? Groupe::findOrFail($groupe)->membres()->orderBy('nom')->pluck('matricule')->implode("\n") : '';
        $this->bilan = null;
    }

    // remplace la liste des membres par les matricules saisis ; signale ceux qui ne sont pas sur la liste électorale
    public function enregistrerMembres(): void
    {
        $groupe = Groupe::findOrFail($this->ouvert);
        $saisis = collect(preg_split('/[\s,;]+/', $this->matricules, -1, PREG_SPLIT_NO_EMPTY))->map(fn ($m) => trim($m))->unique()->values();
        $trouves = User::electeurs()->whereIn('matricule', $saisis)->pluck('id', 'matricule');

        $groupe->membres()->sync($trouves->values());
        $this->bilan = ['membres' => $trouves->count(), 'inconnus' => $saisis->diff($trouves->keys())->values()->all()];
        Journal::noter('groupe_membres', ['groupe' => $groupe->id, 'membres' => $trouves->count()]);
    }

    public function supprimer(int $groupe): void
    {
        $groupe = Groupe::findOrFail($groupe);
        // un scrutin annoncé ne doit pas perdre ses électeurs en cours de route
        abort_if($groupe->scrutins()->whereNotNull('programme_at')->exists(), 409, 'Ce groupe est utilisé par un scrutin programmé.');
        Journal::noter('groupe_supprime', ['groupe' => $groupe->id, 'nom' => $groupe->nom]);
        $groupe->delete();
        $this->ouvert = null;
    }

    public function render()
    {
        return view('livewire.admin.groupes', [
            'groupes' => Groupe::withCount(['membres', 'scrutins' => fn ($q) => $q->whereNotNull('programme_at')])->orderBy('nom')->get(),
        ]);
    }
}
