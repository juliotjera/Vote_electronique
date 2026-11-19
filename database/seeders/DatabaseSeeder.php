<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Groupe;
use App\Models\Scrutin;
use App\Models\User;
use App\Notifications\AvisScrutin;
use App\Services\CycleScrutins;
use App\Services\Urne;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

// Données de démonstration : une école fictive, ses classes, ses électeurs et cinq scrutins,
// un à chaque étape (brouillon, annoncé, ouvert, clos, publié). Les votes passent par l'urne
// elle-même : la chaîne d'empreintes des bulletins est donc valide.
class DatabaseSeeder extends Seeder
{
    private const PRENOMS = ['Jean', 'Nomena', 'Sarah', 'Tojo', 'Fanja', 'Hery', 'Lalao', 'Mamy', 'Voahirana', 'Andry', 'Tiana', 'Rado',
        'Miora', 'Fitia', 'Nirina', 'Toky', 'Hasina', 'Mialy', 'Zo', 'Faniry', 'Haja', 'Onja', 'Rindra', 'Sitraka', 'Ny Aina', 'Tsiry',
        'Volana', 'Malala', 'Fenitra', 'Kanto', 'Lova', 'Mahery', 'Njaka', 'Santatra', 'Diary', 'Hanitra', 'Tahiry', 'Feno', 'Ando', 'Mihaja'];

    private const NOMS = ['RABE', 'RAZAFI', 'RANDRIA', 'ANDRIAM', 'RAKOTO', 'RASOA', 'RAHARISON', 'RAZAFINDRAKOTO', 'RANDRIAMAMPIONONA',
        'RAKOTOMALALA', 'ANDRIANAIVO', 'RATSIMBA', 'RAVELOSON', 'RAJAONARISON', 'RAMANANTSOA', 'RAZANAMPARANY', 'RAHARINIRINA',
        'ANDRIAMANANTENA', 'RAKOTONIRINA', 'RASOLOFO', 'RANAIVO', 'RAMAROSON', 'RABEMANANJARA', 'RANDRIANARIVELO'];

    private int $matricule = 20230001;

    private int $tirage = 0;

    public function run(Urne $urne, CycleScrutins $cycle): void
    {
        $classes = collect(['L1' => 34, 'L2' => 40, 'L3' => 32, 'M1' => 18, 'M2' => 14])
            ->map(fn ($effectif, $nom) => [
                'classe' => Classe::create(['nom' => $nom, 'description' => $this->descriptionClasse($nom)]),
                'electeurs' => collect(),
                'effectif' => $effectif,
            ]);

        User::create([
            'nom' => 'RAKOTOMALALA', 'prenom' => 'Hanta', 'email' => 'admin@vote-demo.mg',
            'role' => 'admin', 'actif' => true, 'demo' => true,
        ]);

        $classes = $classes->map(function (array $c) {
            for ($i = 0; $i < $c['effectif']; $i++) {
                $c['electeurs']->push($this->electeur($c['classe']));
            }

            return $c;
        });
        // un électeur inactif (a quitté l'établissement) : il ne peut ni se connecter ni voter
        $classes['L2']['electeurs']->last()->update(['actif' => false]);

        // un groupe dont les membres viennent de plusieurs classes (association, club)
        Groupe::create(['nom' => 'Club informatique', 'description' => 'Membres à jour de cotisation'])->membres()->attach(
            collect(['L1', 'L2', 'L3', 'M1'])->flatMap(fn ($nom) => $classes[$nom]['electeurs']->take(4)->pluck('id'))
        );

        $this->scrutinPublie($urne, $cycle, $classes['L1']);
        $this->scrutinClos($urne, $classes['L3']);
        $this->scrutinOuvert($urne, $classes['L2']);
        $this->scrutinAnnonce($classes);
        $this->brouillon($classes);
    }

    private function electeur(Classe $classe): User
    {
        $prenom = self::PRENOMS[$this->tirage % count(self::PRENOMS)];
        $nom = self::NOMS[intdiv($this->tirage, 3) % count(self::NOMS)];
        $this->tirage += 7;
        $matricule = (string) $this->matricule++;

        return User::create([
            'matricule' => $matricule,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => strtolower(str_replace(' ', '', $prenom)).'.'.strtolower($nom).'.'.substr($matricule, -3).'@univ-demo.mg',
            'classe_id' => $classe->id,
            'role' => 'electeur',
            'actif' => true,
            'demo' => true,
        ]);
    }

    private function descriptionClasse(string $nom): string
    {
        return ['L1' => 'Licence 1', 'L2' => 'Licence 2', 'L3' => 'Licence 3', 'M1' => 'Master 1', 'M2' => 'Master 2'][$nom];
    }

    private function scrutin(array $donnees, array $classes, ?Carbon $programme = null): Scrutin
    {
        // relu en base : les valeurs par défaut (vote blanc autorisé…) sont alors connues
        $scrutin = Scrutin::create($donnees)->fresh();
        $scrutin->classes()->attach(collect($classes)->pluck('classe.id'));
        if ($programme) {
            $scrutin->forceFill(['programme_at' => $programme])->save();
        }

        return $scrutin->load('classes');
    }

    private function candidat(Scrutin $scrutin, User $user, string $slogan, string $programme, string $statut = 'validee'): Candidature
    {
        return Candidature::create([
            'scrutin_id' => $scrutin->id, 'user_id' => $user->id, 'slogan' => $slogan, 'programme' => $programme,
            'statut' => $statut, 'decide_at' => $statut === 'en_attente' ? null : now()->subDays(3),
        ]);
    }

    // Fait voter une partie des électeurs, selon des poids par candidat (et quelques blancs)
    private function voter(Urne $urne, Scrutin $scrutin, $electeurs, array $poids, int $blancs = 0): void
    {
        $file = [];
        foreach ($poids as $candidatureId => $n) {
            $file = array_merge($file, array_fill(0, $n, $candidatureId));
        }
        $file = array_merge($file, array_fill(0, $blancs, 'blanc'));
        mt_srand($scrutin->id);
        shuffle($file);

        foreach ($electeurs->where('actif', true)->values()->take(count($file)) as $i => $electeur) {
            $choix = $file[$i];
            $urne->voter($electeur, $scrutin, $choix === 'blanc' ? [] : (array) $choix, $choix === 'blanc');
        }
    }

    // L1 : scrutin publié (résultats publics)
    private function scrutinPublie(Urne $urne, CycleScrutins $cycle, array $l1): void
    {
        $ouverture = now()->subWeeks(3)->setTime(8, 0);
        $scrutin = $this->scrutin([
            'titre' => 'Élection des délégués L1 2026',
            'description' => 'Deux délégués de classe représentent la promotion auprès de la direction des études pour l’année.',
            'type' => 'uninominal', 'nombre_choix' => 1, 'nombre_elus' => 1,
            'ouverture_at' => $ouverture, 'cloture_at' => now()->addHour(), // ouvert le temps de voter
        ], [$l1], $ouverture->copy()->subWeek());
        [$a, $b] = $l1['electeurs']->take(2)->all();
        $ca = $this->candidat($scrutin, $a, 'Pour une voix étudiante forte !', "Réunions mensuelles avec les responsables pédagogiques\nGroupe de communication de classe sécurisé\nRelais des difficultés d’emploi du temps");
        $cb = $this->candidat($scrutin, $b, 'À l’écoute, toute l’année', "Permanence chaque vendredi midi\nBoîte à idées en ligne\nTutorat entre L1 et L2 avant les examens");
        $this->voter($urne, $scrutin, $l1['electeurs'], [$ca->id => 15, $cb->id => 12], 2);

        $scrutin->update(['cloture_at' => $ouverture->copy()->addDays(2)->setTime(18, 0)]);
        $cycle->publier($scrutin->fresh('classes'));
        $scrutin->forceFill(['publie_at' => $ouverture->copy()->addDays(3), 'notif_ouverture_at' => $ouverture])->save();
    }

    // L3 : vote clos, résultats à vérifier puis publier par l'administration
    private function scrutinClos(Urne $urne, array $l3): void
    {
        $ouverture = now()->subDays(4)->setTime(8, 0);
        $scrutin = $this->scrutin([
            'titre' => 'Élection des délégués L3 2026',
            'description' => 'Le délégué de L3 siège au conseil de perfectionnement de la licence.',
            'ouverture_at' => $ouverture, 'cloture_at' => now()->addHour(),
        ], [$l3], $ouverture->copy()->subWeek());
        [$a, $b, $c] = $l3['electeurs']->take(3)->all();
        $ca = $this->candidat($scrutin, $a, 'Des stages pour tous', "Forum entreprises au premier semestre\nBanque de CV partagée\nRetours d’anciens stagiaires");
        $cb = $this->candidat($scrutin, $b, 'Réussir ensemble', "Groupes de révision par matière\nSalle ouverte le samedi\nCalendrier des rendus partagé");
        $cc = $this->candidat($scrutin, $c, 'Une L3 connectée', "Wi-Fi fiable dans les salles\nSupports de cours en ligne\nAteliers outils numériques");
        $this->voter($urne, $scrutin, $l3['electeurs'], [$ca->id => 11, $cb->id => 9, $cc->id => 6], 1);
        $scrutin->update(['cloture_at' => now()->subDay()->setTime(18, 0)]);
        $scrutin->forceFill(['notif_ouverture_at' => $ouverture])->save();
    }

    // L2 : vote en cours, un peu plus de la moitié des électeurs a déjà voté
    private function scrutinOuvert(Urne $urne, array $l2): void
    {
        $ouverture = now()->subDay()->setTime(8, 0);
        $scrutin = $this->scrutin([
            'titre' => 'Élection des délégués L2 2026',
            'description' => 'Élection du délégué de la promotion L2 pour l’année universitaire. Un seul nom par bulletin ; le vote blanc est possible.',
            'ouverture_at' => $ouverture, 'cloture_at' => now()->addDays(2)->setTime(18, 0),
            'candidatures_fin_at' => $ouverture->copy()->subDays(2)->setTime(18, 0),
        ], [$l2], $ouverture->copy()->subWeek());
        [$a, $b, $c] = $l2['electeurs']->take(3)->all();
        $ca = $this->candidat($scrutin, $a, 'Une promo unie, une voix entendue', "Point mensuel avec la direction des études\nCalendrier des examens publié un mois à l’avance\nCagnotte de promo transparente, comptes publiés");
        $cb = $this->candidat($scrutin, $b, 'Moins de stress, plus de réussite', "Fiches de révision partagées par matière\nSoutien entre étudiants avant les partiels\nSalle calme réservée pendant les examens");
        $cc = $this->candidat($scrutin, $c, 'Le numérique au service de la promo', "Groupe de classe sécurisé pour les annonces\nSupports de cours centralisés\nSondages rapides avant chaque réunion");
        // avis d'ouverture dans la cloche des électeurs (les comptes de démonstration ne reçoivent pas d'e-mail)
        Notification::sendNow($l2['electeurs']->where('actif', true), new AvisScrutin($scrutin, 'ouverture'));
        $this->voter($urne, $scrutin, $l2['electeurs'], [$ca->id => 9, $cb->id => 8, $cc->id => 5], 1);
        $scrutin->forceFill(['notif_ouverture_at' => $ouverture])->save();
    }

    // BDE : scrutin annoncé, candidatures ouvertes (plurinominal, toutes les classes)
    private function scrutinAnnonce($classes): void
    {
        $ouverture = now()->addDays(5)->setTime(8, 0);
        $scrutin = $this->scrutin([
            'titre' => 'Bureau des étudiants (BDE) 2026',
            'description' => 'Trois membres du bureau sont élus parmi les candidats de toutes les promotions. Cochez jusqu’à trois noms.',
            'type' => 'plurinominal', 'nombre_choix' => 3, 'nombre_elus' => 3,
            'ouverture_at' => $ouverture, 'cloture_at' => $ouverture->copy()->addDays(2)->setTime(18, 0),
            'candidatures_fin_at' => now()->addDays(3)->setTime(18, 0),
        ], $classes->all(), now()->subDays(2));
        $this->candidat($scrutin, $classes['M1']['electeurs'][0], 'Un BDE qui organise, pas qui promet', "Journée d’intégration en début d’année\nTournoi inter-promos\nComptes du BDE publiés chaque trimestre");
        $this->candidat($scrutin, $classes['L3']['electeurs'][5], 'Culture et sport pour tous', "Club photo et club lecture\nCréneaux de foot et de basket réservés\nSortie de fin d’année accessible à tous les budgets");
        $this->candidat($scrutin, $classes['M2']['electeurs'][0], 'Préparer l’après-diplôme', "Rencontres avec les anciens\nAteliers CV et entretiens\nAnnuaire des diplômés", 'en_attente');
        // candidature du compte « Candidat » de la démonstration : modifiable jusqu'à la fin du dépôt
        $this->candidat($scrutin, $classes['L2']['electeurs'][10], 'La voix des L2 au bureau', "Relayer les besoins des L2 au BDE\nUn événement par semestre organisé avec la promo\nDes comptes rendus après chaque réunion du bureau", 'en_attente');
    }

    private function brouillon($classes): void
    {
        $ouverture = now()->addWeeks(3)->setTime(8, 0);
        $this->scrutin([
            'titre' => 'Représentants Master au conseil pédagogique',
            'description' => 'Un représentant par niveau de Master siège au conseil pédagogique.',
            'type' => 'plurinominal', 'nombre_choix' => 2, 'nombre_elus' => 2,
            'ouverture_at' => $ouverture, 'cloture_at' => $ouverture->copy()->addDays(2)->setTime(18, 0),
            'candidatures_fin_at' => $ouverture->copy()->subDays(3)->setTime(18, 0),
        ], [$classes['M1'], $classes['M2']]);
    }
}
