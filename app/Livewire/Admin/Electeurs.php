<?php

namespace App\Livewire\Admin;

use App\Models\Classe;
use App\Models\Journal;
use App\Models\User;
use App\Services\ImportElecteurs;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

// Liste électorale : recherche, filtre par classe, activation, import CSV
#[Layout('layouts::app')]
#[Title('Électeurs')]
class Electeurs extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $recherche = '';

    #[Url]
    public ?int $classe = null;

    public $fichier = null;

    public ?array $bilan = null;

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }

    public function updatedClasse(): void
    {
        $this->resetPage();
    }

    public function basculer(User $electeur): void
    {
        abort_if($electeur->estAdmin(), 403);
        $electeur->update(['actif' => ! $electeur->actif]);
        Journal::noter($electeur->actif ? 'electeur_active' : 'electeur_desactive', ['electeur' => $electeur->id]);
    }

    public function importer(ImportElecteurs $import): void
    {
        $this->validate(['fichier' => 'required|file|mimes:csv,txt|max:2048'], [], ['fichier' => 'fichier']);
        $this->bilan = $import->depuisFichier($this->fichier->getRealPath());
        Journal::noter('import_electeurs', ['crees' => $this->bilan['crees'], 'mis_a_jour' => $this->bilan['mis_a_jour']]);
        $this->fichier = null;
    }

    public function render()
    {
        $electeurs = User::electeurs()->with('classe')
            ->when($this->classe, fn ($q) => $q->where('classe_id', $this->classe))
            ->when($this->recherche, function ($q) {
                $terme = '%'.mb_strtolower($this->recherche).'%';
                $q->where(fn ($q) => $q->whereRaw('lower(nom) like ?', [$terme])->orWhereRaw('lower(prenom) like ?', [$terme])
                    ->orWhere('matricule', 'like', $terme)->orWhereRaw('lower(email) like ?', [$terme]));
            })
            ->orderBy('nom')->paginate(20);

        return view('livewire.admin.electeurs', [
            'electeurs' => $electeurs,
            'classes' => Classe::orderBy('nom')->get(),
        ]);
    }
}
