<?php

namespace App\Http\Controllers;

use App\Enums\EtatScrutin;
use App\Models\Emargement;
use App\Models\Journal;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\Depouillement;
use App\Services\Urne;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Procès-verbal des résultats (PDF), tableau des voix, liste électorale et liste d'émargement (CSV, ouvrables dans Excel)
class ExportController extends Controller
{
    private function exportable(Scrutin $scrutin): void
    {
        abort_unless(in_array($scrutin->etat(), [EtatScrutin::Clos, EtatScrutin::Publie], true), 409, 'Les résultats s’exportent après la clôture du vote.');
    }

    public function pdf(Scrutin $scrutin, Depouillement $depouillement, Urne $urne)
    {
        $this->exportable($scrutin);
        Journal::noter('export_pdf', ['scrutin' => $scrutin->id]);

        return Pdf::loadView('exports.proces-verbal', [
            'scrutin' => $scrutin->load(['classes', 'groupes']),
            'r' => $depouillement->resultats($scrutin),
            'integrite' => $urne->verifierIntegrite($scrutin),
        ])->setOption('isFontSubsettingEnabled', true) // seules les lettres utilisées : PDF léger
            ->download('resultats-'.Str::slug($scrutin->titre).'.pdf');
    }

    public function csv(Scrutin $scrutin, Depouillement $depouillement)
    {
        $this->exportable($scrutin);
        $r = $depouillement->resultats($scrutin);
        Journal::noter('export_csv', ['scrutin' => $scrutin->id]);

        return response()->streamDownload(function () use ($r) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM : accents corrects dans Excel
            fputcsv($sortie, ['Rang', 'Candidat', 'Matricule', 'Voix', '% des bulletins exprimés', 'Élu'], ';');
            foreach ($r['candidats'] as $i => $c) {
                fputcsv($sortie, [$i + 1, $c->user->nomComplet(), $c->user->matricule, $c->voix, str_replace('.', ',', $c->pourcentage), $c->elu ? 'oui' : ($c->exAequo ? 'égalité' : 'non')], ';');
            }
            fputcsv($sortie, [], ';');
            foreach ([['Inscrits', $r['inscrits']], ['Votants', $r['votants']], ['Participation (%)', str_replace('.', ',', $r['participation'])], ['Votes blancs', $r['blancs']]] as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        }, 'resultats-'.Str::slug($scrutin->titre).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Liste électorale au format d'import de la scolarité : le fichier exporté se réimporte tel quel
    public function electeurs(Request $request): StreamedResponse
    {
        $classe = $request->integer('classe') ?: null;
        Journal::noter('export_electeurs', $classe ? ['classe' => $classe] : []);
        $electeurs = User::electeurs()->with('classe')->when($classe, fn ($q) => $q->where('classe_id', $classe))
            ->orderBy('nom')->orderBy('prenom')->get();

        return $this->tableau('liste-electorale.csv', ['Matricule', 'Nom', 'Prénom', 'Email', 'Statut', 'Classe'],
            $electeurs->map(fn ($e) => [$e->matricule, $e->nom, $e->prenom, $e->email, $e->actif ? 'actif' : 'inactif', $e->classe?->nom]));
    }

    // Liste d'émargement : QUI a voté et quel jour, jamais quoi. Triée par nom, jamais dans l'ordre des votes,
    // pour qu'aucun rapprochement avec l'ordre des bulletins ne soit possible.
    public function emargement(Scrutin $scrutin): StreamedResponse
    {
        abort_if($scrutin->purge_at !== null, 410, 'La liste d’émargement a été supprimée 30 jours après la clôture.');
        Journal::noter('export_emargement', ['scrutin' => $scrutin->id]);
        $lignes = Emargement::where('scrutin_id', $scrutin->id)->join('users', 'users.id', '=', 'emargements.user_id')
            ->leftJoin('classes', 'classes.id', '=', 'users.classe_id')
            ->orderBy('users.nom')->orderBy('users.prenom')
            ->get(['users.matricule', 'users.nom', 'users.prenom', 'classes.nom as classe', 'emargements.date_vote']);

        return $this->tableau('emargement-'.Str::slug($scrutin->titre).'.csv', ['Matricule', 'Nom', 'Prénom', 'Classe', 'Date du vote'],
            $lignes->map(fn ($l) => [$l->matricule, $l->nom, $l->prenom, $l->classe, $l->date_vote?->format('d/m/Y')]));
    }

    private function tableau(string $nom, array $entetes, iterable $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM : accents corrects dans Excel
            fputcsv($sortie, $entetes, ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
