<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Import de la liste électorale au format remis par la scolarité :
//   Matricule,Nom,Prénom,Email,Statut,Classe
//   20230001,RABE,Jean,jean.rabe@univ-mg.edu,actif,L2
// Séparateur virgule ou point-virgule (export Excel), en-têtes avec ou sans accents.
// Un matricule déjà connu est mis à jour ; une classe inconnue est créée.
class ImportElecteurs
{
    private const COLONNES = ['matricule', 'nom', 'prenom', 'email', 'statut', 'classe'];

    /** @return array{crees: int, mis_a_jour: int, erreurs: array<string>} */
    public function depuisFichier(string $chemin): array
    {
        $contenu = file_get_contents($chemin);
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu); // BOM d'Excel
        $lignes = preg_split('/\r\n|\r|\n/', trim($contenu));
        $separateur = substr_count($lignes[0] ?? '', ';') > substr_count($lignes[0] ?? '', ',') ? ';' : ',';

        $entetes = array_map(fn ($e) => Str::of($e)->ascii()->lower()->replace(['-', ' ', '_'], '')->toString(), str_getcsv(array_shift($lignes), $separateur));
        $positions = [];
        foreach (self::COLONNES as $colonne) {
            $position = array_search($colonne, $entetes, true);
            if ($position === false) {
                return ['crees' => 0, 'mis_a_jour' => 0, 'erreurs' => ["Colonne « {$colonne} » absente. En-têtes attendus : Matricule,Nom,Prénom,Email,Statut,Classe"]];
            }
            $positions[$colonne] = $position;
        }

        $bilan = ['crees' => 0, 'mis_a_jour' => 0, 'erreurs' => []];
        $classes = Classe::pluck('id', 'nom');

        DB::transaction(function () use ($lignes, $separateur, $positions, &$bilan, $classes) {
            foreach ($lignes as $i => $ligne) {
                if (trim($ligne) === '') {
                    continue;
                }
                $valeurs = str_getcsv($ligne, $separateur);
                $d = [];
                foreach ($positions as $colonne => $position) {
                    $d[$colonne] = trim($valeurs[$position] ?? '');
                }
                $d['email'] = mb_strtolower($d['email']);
                $numero = $i + 2;

                $controle = Validator::make($d, [
                    'matricule' => 'required|max:20',
                    'nom' => 'required|max:80',
                    'prenom' => 'required|max:80',
                    'email' => 'required|email|max:255',
                    'statut' => 'nullable|in:actif,inactif',
                    'classe' => 'required|max:50',
                ]);
                if ($controle->fails()) {
                    $bilan['erreurs'][] = "Ligne {$numero} : ".$controle->errors()->first();

                    continue;
                }
                $existant = User::where('matricule', $d['matricule'])->first();
                if (User::where('email', $d['email'])->where('matricule', '!=', $d['matricule'])->exists()) {
                    $bilan['erreurs'][] = "Ligne {$numero} : l'e-mail {$d['email']} appartient déjà à un autre matricule.";

                    continue;
                }
                if (! isset($classes[$d['classe']])) {
                    $classes[$d['classe']] = Classe::create(['nom' => $d['classe']])->id;
                }

                User::updateOrCreate(['matricule' => $d['matricule']], [
                    'nom' => mb_strtoupper($d['nom']),
                    'prenom' => $d['prenom'],
                    'email' => $d['email'],
                    'classe_id' => $classes[$d['classe']],
                    'actif' => ($d['statut'] ?: 'actif') === 'actif',
                    'role' => $existant?->role ?? 'electeur',
                ]);
                $existant ? $bilan['mis_a_jour']++ : $bilan['crees']++;
            }
        });

        return $bilan;
    }
}
