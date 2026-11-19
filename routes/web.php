<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PagesController;
use App\Http\Middleware\DefinirLangue;
use App\Livewire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public
Route::get('/', [PagesController::class, 'accueil'])->name('accueil');
Route::get('/resultats', [PagesController::class, 'resultatsPublics'])->name('resultats.publics');
Route::get('/resultats/{scrutin}', [PagesController::class, 'resultats'])->name('resultats');
Route::get('/urne/{scrutin}', [PagesController::class, 'urne'])->name('urne');
Route::get('/confidentialite', [PagesController::class, 'confidentialite'])->name('confidentialite');
Route::get('/aide', [PagesController::class, 'aide'])->name('aide');
Route::get('/aide.pdf', [PagesController::class, 'aidePdf'])->name('aide.pdf');
Route::get('/photos/{photo}', [PagesController::class, 'photo'])->name('photo');
Route::livewire('/verifier', Livewire\VerifierRecu::class)->name('verifier');
Route::livewire('/connexion', Livewire\Connexion::class)->middleware('guest')->name('connexion');

Route::get('/langue/{langue}', function (Request $request, string $langue) {
    abort_unless(array_key_exists($langue, DefinirLangue::LANGUES), 404);
    $request->session()->put('langue', $langue);

    return back();
})->name('langue');

Route::post('/deconnexion', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('accueil');
})->middleware('auth')->name('deconnexion');

// ── Électeurs
Route::middleware(['auth', 'electeur'])->group(function () {
    Route::livewire('/espace', Livewire\Electeur\Espace::class)->name('espace');
    Route::livewire('/scrutins/{scrutin}', Livewire\Electeur\Voter::class)->name('scrutin');
    Route::livewire('/scrutins/{scrutin}/candidature', Livewire\Electeur\Candidater::class)->name('candidater');
    Route::livewire('/notifications', Livewire\Electeur\Notifications::class)->name('notifications');
    Route::livewire('/mes-donnees', Livewire\Electeur\MesDonnees::class)->name('mes-donnees');
});

// ── Administration
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', Livewire\Admin\Tableau::class)->name('tableau');
    Route::livewire('/scrutins', Livewire\Admin\Scrutins::class)->name('scrutins');
    Route::livewire('/scrutins/nouveau', Livewire\Admin\FormulaireScrutin::class)->name('scrutins.nouveau');
    Route::livewire('/scrutins/{scrutin}/modifier', Livewire\Admin\FormulaireScrutin::class)->name('scrutins.modifier');
    Route::livewire('/scrutins/{scrutin}', Livewire\Admin\Suivi::class)->name('scrutins.suivi');
    Route::get('/scrutins/{scrutin}/resultats.pdf', [ExportController::class, 'pdf'])->name('scrutins.pdf');
    Route::get('/scrutins/{scrutin}/resultats.csv', [ExportController::class, 'csv'])->name('scrutins.csv');
    Route::get('/scrutins/{scrutin}/emargement.csv', [ExportController::class, 'emargement'])->name('scrutins.emargement');
    Route::get('/electeurs.csv', [ExportController::class, 'electeurs'])->name('electeurs.csv');
    Route::livewire('/candidatures', Livewire\Admin\Candidatures::class)->name('candidatures');
    Route::livewire('/electeurs', Livewire\Admin\Electeurs::class)->name('electeurs');
    Route::livewire('/groupes', Livewire\Admin\Groupes::class)->name('groupes');
    Route::livewire('/journal', Livewire\Admin\JournalActivite::class)->name('journal');
});

// Adresse inconnue : la page 404 passe par le groupe « web », donc dans la langue choisie (FR / MG)
Route::fallback(fn () => abort(404));
