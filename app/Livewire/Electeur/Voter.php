<?php

namespace App\Livewire\Electeur;

use App\Enums\EtatScrutin;
use App\Models\Journal;
use App\Models\Scrutin;
use App\Services\Urne;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Page d'un scrutin pour l'électeur : candidats, puis vote en trois temps
// (choisir → vérifier son bulletin → déposer) et remise du reçu.
#[Layout('layouts::app')]
class Voter extends Component
{
    public Scrutin $scrutin;

    public string $etape = 'choisir'; // choisir → confirmer → recu

    /** @var array<int> */
    public array $choix = [];

    public bool $blanc = false;

    public ?string $recu = null;

    public function mount(Scrutin $scrutin): void
    {
        $scrutin->load(['classes', 'groupes']);
        if ($scrutin->etat() === EtatScrutin::Brouillon || ! auth()->user()->estElecteurDe($scrutin)) {
            Journal::noter('acces_refuse', ['scrutin' => $scrutin->id]);
            abort(404);
        }
        $this->scrutin = $scrutin;
    }

    // Uninominal : un clic remplace le choix ; plurinominal : coche / décoche, dans la limite permise
    public function basculer(int $candidature): void
    {
        $this->blanc = false;
        if ($this->scrutin->nombre_choix === 1) {
            $this->choix = [$candidature];

            return;
        }
        if (in_array($candidature, $this->choix, true)) {
            $this->choix = array_values(array_diff($this->choix, [$candidature]));
        } elseif (count($this->choix) < $this->scrutin->nombre_choix) {
            $this->choix[] = $candidature;
        }
    }

    public function voterBlanc(): void
    {
        $this->blanc = ! $this->blanc;
        $this->choix = [];
    }

    public function verifierBulletin(): void
    {
        if (! $this->blanc && ! $this->choix) {
            $this->addError('vote', 'Choisissez un candidat, ou votez blanc.');

            return;
        }
        $this->etape = 'confirmer';
    }

    public function modifier(): void
    {
        $this->etape = 'choisir';
    }

    public function deposer(Urne $urne): void
    {
        try {
            $this->recu = $urne->voter(auth()->user(), $this->scrutin, $this->choix, $this->blanc);
        } catch (ValidationException $e) {
            // tentative refusée (double vote, hors délai, bulletin non conforme) : aucun bulletin n'est créé,
            // l'entrée ne dit donc rien d'un choix
            Journal::noter('vote_refuse', ['scrutin' => $this->scrutin->id, 'motif' => collect($e->errors())->flatten()->first()]);
            throw $e;
        }
        $this->etape = 'recu';
        $this->reset('choix', 'blanc');
    }

    public function render()
    {
        $user = auth()->user();
        $candidats = $this->scrutin->candidatures()->validees()->with('user.classe')->orderBy('id')->get();

        return view('livewire.electeur.voter', [
            'etat' => $this->scrutin->etat(),
            'candidats' => $candidats,
            'dejaVote' => $this->recu === null && $user->aVote($this->scrutin),
            'emargement' => $user->emargements()->where('scrutin_id', $this->scrutin->id)->first(),
            'maCandidature' => $user->candidatures()->where('scrutin_id', $this->scrutin->id)->first(),
            'selection' => $candidats->whereIn('id', $this->choix),
        ])->title($this->scrutin->titre);
    }
}
