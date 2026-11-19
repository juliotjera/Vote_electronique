<?php

namespace App\Support;

// Guide d'utilisation (livrable du cahier des charges) : un seul contenu pour la page /aide et son PDF
class Guide
{
    /** @return array<int, array{id: string, titre: string, pour: string, etapes: array<int, array{0: string, 1: string}>}> */
    public static function sections(): array
    {
        return [
            [
                'id' => 'voter', 'titre' => 'Voter', 'pour' => 'Électeurs',
                'etapes' => [
                    ['Se connecter', 'Sur la page de connexion, choisissez « Électeur », saisissez votre matricule et votre adresse e-mail tels qu’inscrits sur la liste électorale, puis le code à 6 chiffres reçu par e-mail. Le code est valable 10 minutes et ne sert qu’une fois.'],
                    ['Ouvrir le scrutin', 'Dans « Mes scrutins », les votes de votre classe et de vos groupes apparaissent avec leur état : annoncé, ouvert, clos ou publié. Un compte à rebours indique la clôture.'],
                    ['Choisir', 'Lisez les candidatures (photo, slogan, engagements) et appuyez sur « Choisir ». En plurinominal, cochez jusqu’au nombre de noms indiqué. Vous pouvez aussi voter blanc.'],
                    ['Relire et déposer', '« Vérifier mon bulletin » affiche votre bulletin une dernière fois. « Modifier » revient au choix ; « Déposer mon bulletin » le met dans l’urne. Une fois déposé, il ne peut plus être modifié.'],
                    ['Garder son reçu', 'Après le dépôt, un reçu de 12 caractères et un QR code s’affichent une seule fois. Téléchargez l’image ou copiez le code : il prouve que votre bulletin est dans l’urne sans dire pour qui vous avez voté.'],
                ],
            ],
            [
                'id' => 'verifier', 'titre' => 'Vérifier son vote', 'pour' => 'Électeurs et public',
                'etapes' => [
                    ['Urne publique', 'Chaque scrutin publie la liste de ses bulletins (numéro et empreintes, jamais leur contenu). Saisissez votre reçu dans « Retrouver mon bulletin » : votre bulletin est surligné. Le calcul se fait sur votre téléphone, le reçu n’est pas envoyé.'],
                    ['Vérifier un reçu', 'La page « Vérifier un reçu » indique si un bulletin correspond au reçu et si la chaîne d’empreintes de l’urne est intacte.'],
                    ['Résultats', 'Après publication, les résultats affichent inscrits, votants, participation, blancs, voix et pourcentage de chaque candidat, élus et égalités.'],
                ],
            ],
            [
                'id' => 'candidater', 'titre' => 'Se porter candidat', 'pour' => 'Électeurs',
                'etapes' => [
                    ['Déposer', 'Quand un scrutin est annoncé, ouvrez-le et choisissez « Déposer ma candidature » : photo (facultative), slogan, programme (un engagement par ligne), avant la date limite.'],
                    ['Suivre la décision', 'L’administration valide ou refuse la candidature avec un motif ; vous êtes prévenu par e-mail et dans la cloche. Une candidature refusée se corrige et se renvoie ; elle se retire tant que le dépôt est ouvert.'],
                ],
            ],
            [
                'id' => 'compte', 'titre' => 'Mes données et mes rappels', 'pour' => 'Électeurs',
                'etapes' => [
                    ['Consulter et corriger', '« Mes données » montre tout ce que la plateforme sait de vous. Vous corrigez vous-même votre nom ; pour le matricule, l’e-mail ou la classe, adressez-vous à l’administration.'],
                    ['Choisir ses rappels', 'Si vous n’avez pas voté, un rappel vous prévient avant la clôture : la veille (24 h avant), le jour même (3 h avant) ou seulement dans la cloche. Vous choisissez aussi de recevoir par e-mail l’ouverture et les résultats.'],
                    ['Langue', 'Le bouton FR / MG en haut de l’écran passe l’espace électeur en français ou en malgache.'],
                ],
            ],
            [
                'id' => 'organiser', 'titre' => 'Organiser un scrutin', 'pour' => 'Administration',
                'etapes' => [
                    ['Liste électorale', '« Électeurs » importe le fichier CSV de la scolarité (Matricule, Nom, Prénom, Email, Statut, Classe ; virgule ou point-virgule). Les erreurs sont signalées ligne par ligne. « Exporter (CSV) » produit le même format.'],
                    ['Groupes', '« Groupes » réunit des électeurs de plusieurs classes (association, club, comité) : collez la liste de leurs matricules.'],
                    ['Créer le scrutin', '« Nouveau scrutin » : intitulé, mode (uninominal ou plurinominal), nombre de choix et d’élus, vote blanc, dates, classes et groupes concernés, résultats publics ou non. Le scrutin reste un brouillon modifiable.'],
                    ['Programmer', '« Programmer » annonce le scrutin aux électeurs ; il ne se modifie plus. L’ouverture et la clôture se font seules aux dates prévues.'],
                    ['Valider les candidatures', '« Candidatures » : validez ou refusez avec un motif, avant l’ouverture du vote.'],
                ],
            ],
            [
                'id' => 'suivre', 'titre' => 'Suivre et publier', 'pour' => 'Administration',
                'etapes' => [
                    ['Pendant le vote', 'Le suivi du scrutin affiche la participation en direct et l’intégrité de l’urne. Les voix restent cachées jusqu’à la clôture.'],
                    ['Liste d’émargement', 'Elle indique qui a voté et quel jour, jamais pour qui ; triée par nom, exportable en CSV, supprimée automatiquement 30 jours après la clôture.'],
                    ['Publier', 'Après la clôture, le dépouillement est immédiat. « Publier les résultats » prévient les électeurs. Le procès-verbal PDF et le tableau CSV se téléchargent depuis le suivi.'],
                    ['Journal', 'Le journal trace les actions d’administration et les tentatives refusées (connexions, codes, votes en double), avec une adresse IP tronquée. Il ne contient jamais de vote.'],
                ],
            ],
        ];
    }
}
