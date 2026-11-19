<?php

namespace App\Livewire\Electeur;

use App\Enums\EtatScrutin;
use App\Models\Candidature;
use App\Models\Journal;
use App\Models\Photo;
use App\Models\Scrutin;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

// Dépôt (ou modification) d'une candidature : photo, slogan, programme.
// Elle n'est présentée aux électeurs qu'après validation par l'administration.
#[Layout('layouts::app')]
class Candidater extends Component
{
    use WithFileUploads;

    public Scrutin $scrutin;

    public ?Candidature $candidature = null;

    public string $slogan = '';

    public string $programme = '';

    public $photo = null;

    public function mount(Scrutin $scrutin): void
    {
        $scrutin->load(['classes', 'groupes']);
        abort_if($scrutin->etat() === EtatScrutin::Brouillon || ! auth()->user()->estElecteurDe($scrutin), 404);
        $this->scrutin = $scrutin;
        $this->candidature = auth()->user()->candidatures()->where('scrutin_id', $scrutin->id)->first();
        $this->slogan = $this->candidature?->slogan ?? '';
        $this->programme = $this->candidature?->programme ?? '';
    }

    public function modifiable(): bool
    {
        return $this->scrutin->candidaturesOuvertes() && $this->candidature?->statut !== 'validee';
    }

    public function enregistrer(): void
    {
        abort_unless($this->modifiable(), 403, __('Le dépôt des candidatures est clos pour ce scrutin.'));
        $this->validate([
            'slogan' => 'required|string|min:5|max:120',
            'programme' => 'required|string|min:30|max:3000',
            'photo' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
        ], [], ['slogan' => __('slogan'), 'programme' => __('programme'), 'photo' => __('photo')]);

        $donnees = [
            'slogan' => trim($this->slogan),
            'programme' => trim($this->programme),
            'statut' => 'en_attente', // une candidature modifiée repasse en validation
            'motif_rejet' => null,
        ];
        if ($this->photo) {
            $donnees['photo_id'] = $this->enregistrerPhoto($this->photo)->id;
        }

        $this->candidature = Candidature::updateOrCreate(
            ['scrutin_id' => $this->scrutin->id, 'user_id' => auth()->id()],
            $donnees,
        );
        Journal::noter('candidature_deposee', ['scrutin' => $this->scrutin->id]);
        $this->photo = null;
        session()->flash('succes', __('Candidature envoyée. Vous serez prévenu dès qu’elle sera examinée.'));
    }

    public function retirer(): void
    {
        abort_unless($this->candidature && $this->scrutin->candidaturesOuvertes(), 403);
        $this->candidature->delete();
        Journal::noter('candidature_retiree', ['scrutin' => $this->scrutin->id]);
        session()->flash('succes', __('Candidature retirée.'));
        $this->redirect(route('scrutin', $this->scrutin), navigate: true);
    }

    // Photo réduite à 480 px et réencodée en JPEG : légère sur réseau faible, sans métadonnées
    private function enregistrerPhoto(UploadedFile $fichier): Photo
    {
        $source = imagecreatefromstring(file_get_contents($fichier->getRealPath()));
        $largeur = imagesx($source);
        $hauteur = imagesy($source);
        $cote = min($largeur, $hauteur);
        $image = imagecreatetruecolor(480, 480);
        imagecopyresampled($image, $source, 0, 0, intdiv($largeur - $cote, 2), intdiv($hauteur - $cote, 2), 480, 480, $cote, $cote);
        ob_start();
        imagejpeg($image, null, 82);

        return Photo::create(['type' => 'image/jpeg', 'contenu' => ob_get_clean()]);
    }

    public function render()
    {
        return view('livewire.electeur.candidater', ['modifiable' => $this->modifiable()])->title(__('Ma candidature'));
    }
}
