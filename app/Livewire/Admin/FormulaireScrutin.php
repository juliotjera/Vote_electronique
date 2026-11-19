<?php

namespace App\Livewire\Admin;

use App\Models\Classe;
use App\Models\Groupe;
use App\Models\Journal;
use App\Models\Scrutin;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Création et modification d'un scrutin (tant qu'il est en brouillon)
#[Layout('layouts::app')]
class FormulaireScrutin extends Component
{
    public ?Scrutin $scrutin = null;

    public string $titre = '';

    public string $description = '';

    public string $type = 'uninominal';

    public int $nombre_choix = 1;

    public int $nombre_elus = 1;

    public bool $vote_blanc = true;

    public bool $resultats_publics = true;

    public string $candidatures_fin_at = '';

    public string $ouverture_at = '';

    public string $cloture_at = '';

    /** @var array<int> */
    public array $classes = [];

    /** @var array<int> */
    public array $groupes = [];

    public function mount(?Scrutin $scrutin = null): void
    {
        if ($scrutin?->exists) {
            abort_unless($scrutin->estModifiable(), 403, 'Un scrutin programmé ne se modifie plus.');
            $this->scrutin = $scrutin;
            $this->fill($scrutin->only(['titre', 'type', 'nombre_choix', 'nombre_elus', 'vote_blanc', 'resultats_publics']));
            $this->description = $scrutin->description ?? '';
            $this->candidatures_fin_at = $scrutin->candidatures_fin_at?->format('Y-m-d\TH:i') ?? '';
            $this->ouverture_at = $scrutin->ouverture_at->format('Y-m-d\TH:i');
            $this->cloture_at = $scrutin->cloture_at->format('Y-m-d\TH:i');
            $this->classes = $scrutin->classes()->pluck('classes.id')->all();
            $this->groupes = $scrutin->groupes()->pluck('groupes.id')->all();
        } else {
            // valeurs proposées : ouverture dans une semaine à 8 h, trois jours de vote (cahier des charges)
            $ouverture = now()->addWeek()->setTime(8, 0);
            $this->ouverture_at = $ouverture->format('Y-m-d\TH:i');
            $this->cloture_at = $ouverture->copy()->addDays(2)->setTime(18, 0)->format('Y-m-d\TH:i');
            $this->candidatures_fin_at = $ouverture->copy()->subDays(2)->setTime(18, 0)->format('Y-m-d\TH:i');
        }
    }

    public function updatedType(string $type): void
    {
        if ($type === 'uninominal') {
            $this->nombre_choix = 1;
        }
    }

    public function enregistrer()
    {
        $this->validate([
            'titre' => 'required|string|max:160',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:uninominal,plurinominal',
            'nombre_choix' => 'required|integer|min:1|max:20',
            'nombre_elus' => 'required|integer|min:1|max:20',
            'candidatures_fin_at' => 'nullable|date|before_or_equal:ouverture_at',
            'ouverture_at' => 'required|date',
            'cloture_at' => 'required|date|after:ouverture_at',
            'classes' => 'required_without:groupes|array',
            'classes.*' => 'exists:classes,id',
            'groupes' => 'array',
            'groupes.*' => 'exists:groupes,id',
        ], [
            'cloture_at.after' => 'La clôture doit suivre l’ouverture.',
            'candidatures_fin_at.before_or_equal' => 'Les candidatures doivent se terminer avant l’ouverture du vote.',
            'classes.required_without' => 'Choisissez au moins une classe ou un groupe.',
        ], [
            'titre' => 'intitulé', 'nombre_choix' => 'nombre de choix', 'nombre_elus' => 'nombre d’élus',
            'ouverture_at' => 'ouverture', 'cloture_at' => 'clôture', 'candidatures_fin_at' => 'fin des candidatures',
        ]);

        $donnees = [
            'titre' => trim($this->titre),
            'description' => trim($this->description) ?: null,
            'type' => $this->type,
            'nombre_choix' => $this->type === 'uninominal' ? 1 : $this->nombre_choix,
            'nombre_elus' => $this->nombre_elus,
            'vote_blanc' => $this->vote_blanc,
            'resultats_publics' => $this->resultats_publics,
            'candidatures_fin_at' => $this->candidatures_fin_at ? Carbon::parse($this->candidatures_fin_at) : null,
            'ouverture_at' => Carbon::parse($this->ouverture_at),
            'cloture_at' => Carbon::parse($this->cloture_at),
        ];

        if ($this->scrutin) {
            $this->scrutin->update($donnees);
        } else {
            $this->scrutin = Scrutin::create($donnees + ['cree_par' => auth()->id()]);
        }
        $this->scrutin->classes()->sync($this->classes);
        $this->scrutin->groupes()->sync($this->groupes);
        Journal::noter($this->scrutin->wasRecentlyCreated ? 'scrutin_cree' : 'scrutin_modifie', ['scrutin' => $this->scrutin->id]);

        session()->flash('succes', 'Scrutin enregistré en brouillon. Programmez-le pour l’annoncer aux électeurs.');

        return $this->redirect(route('admin.scrutins.suivi', $this->scrutin), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.formulaire-scrutin', [
            'toutesClasses' => Classe::withCount(['electeurs' => fn ($q) => $q->where('actif', true)])->orderBy('nom')->get(),
            'tousGroupes' => Groupe::withCount(['membres' => fn ($q) => $q->where('actif', true)])->orderBy('nom')->get(),
        ])->title($this->scrutin ? 'Modifier le scrutin' : 'Nouveau scrutin');
    }
}
