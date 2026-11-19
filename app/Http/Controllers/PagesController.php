<?php

namespace App\Http\Controllers;

use App\Enums\EtatScrutin;
use App\Models\Photo;
use App\Models\Scrutin;
use App\Services\Depouillement;
use App\Services\Urne;
use App\Support\Guide;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function accueil()
    {
        $publies = Scrutin::whereNotNull('publie_at')->where('resultats_publics', true)->count();
        $enCours = Scrutin::whereNotNull('programme_at')->whereNull('publie_at')
            ->where('ouverture_at', '<=', now())->where('cloture_at', '>', now())->count();

        // l'écran de vote montré en vitrine : le vrai bulletin du scrutin ouvert, s'il y en a un
        $vitrine = Scrutin::whereNotNull('programme_at')->whereNull('publie_at')->where('resultats_publics', true)
            ->where('ouverture_at', '<=', now())->where('cloture_at', '>', now())
            ->with(['candidatures' => fn ($q) => $q->validees()->with('user')])->orderBy('cloture_at')->first();

        return view('pages.accueil', compact('publies', 'enCours', 'vitrine'));
    }

    public function resultatsPublics()
    {
        $scrutins = Scrutin::whereNotNull('publie_at')->where('resultats_publics', true)
            ->with(['classes', 'groupes'])->latest('publie_at')->get();

        return view('pages.resultats-publics', compact('scrutins'));
    }

    // Résultats d'un scrutin : publics si l'organisateur l'a choisi, sinon réservés à ses électeurs
    public function resultats(Request $request, Scrutin $scrutin, Depouillement $depouillement, Urne $urne)
    {
        $user = $request->user();
        $publie = $scrutin->etat() === EtatScrutin::Publie;
        $autorise = $user?->estAdmin()
            || ($publie && $scrutin->resultats_publics)
            || ($publie && $user?->estElecteurDe($scrutin->load(['classes', 'groupes'])));
        abort_unless($autorise, 404);

        return view('pages.resultats', [
            'scrutin' => $scrutin->load(['classes', 'groupes']),
            'r' => $depouillement->resultats($scrutin),
            'integrite' => $urne->verifierIntegrite($scrutin),
        ]);
    }

    // Urne publique : tous les bulletins scellés d'un scrutin (numéro, empreinte du reçu, empreinte
    // chaînée), jamais leur contenu. Chacun y retrouve son bulletin avec son reçu, calculé dans
    // le navigateur : le reçu ne quitte pas le téléphone.
    public function urne(Scrutin $scrutin, Urne $urne)
    {
        abort_if($scrutin->etat() === EtatScrutin::Brouillon, 404);

        return view('pages.urne', [
            'scrutin' => $scrutin->load(['classes', 'groupes']),
            'bulletins' => $scrutin->bulletins()->orderByDesc('rang')->get(['rang', 'recu_empreinte', 'empreinte']),
            'integrite' => $urne->verifierIntegrite($scrutin),
        ]);
    }

    // Guide d'utilisation (livrable du cahier des charges), en page et en PDF
    public function aide()
    {
        return view('pages.aide', ['sections' => Guide::sections()]);
    }

    public function aidePdf()
    {
        return Pdf::loadView('exports.guide', ['sections' => Guide::sections()])
            ->setOption('isFontSubsettingEnabled', true)
            ->download('guide-vote-electronique.pdf');
    }

    public function confidentialite()
    {
        return view('pages.confidentialite');
    }

    public function photo(Photo $photo)
    {
        return response($photo->contenu, 200, [
            'Content-Type' => $photo->type,
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
