# Rapport de tests fonctionnels — Vote électronique

**Date** : 20 novembre 2026 · **Version** : Laravel 13.42, Livewire 4.4, PHP 8.4 · **Base de test** : SQLite en mémoire, recréée à chaque test.

## 1. Synthèse

| Type de test | Outil | Résultat |
| --- | --- | --- |
| Tests automatisés (fonctionnels et de sécurité) | PHPUnit, `php artisan test` ; sur SQLite et PostgreSQL dans GitHub Actions | **51 / 51 réussis**, 258 vérifications |
| Test de charge (500 électeurs) | PHPUnit (`ChargeTest`) | **réussi** : 500 votes en 1,6 à 1,8 s (3 à 4 ms par vote), dépouillement et contrôle de l’urne en 0,1 s |
| Accessibilité WCAG 2.2 AA | axe-core 4.10, dans le navigateur | **0 erreur** sur les écrans audités (public, électeur, administration) |
| Affichage sur téléphone (375 px) | navigateur, mesure de la largeur | aucune page ne déborde |
| Parcours au clavier | manuel | vote complet au clavier, focus visible, focus déplacé à chaque étape |

## 2. Couverture du cahier des charges

| Exigence (cahier des charges de L1) | Test(s) |
| --- | --- |
| 1.1–1.2 Identification par matricule et e-mail, code envoyé par e-mail | `ConnexionTest` : code reçu ouvre la session ; matricule et e-mail du même compte ; code limité à 5 essais, expiré, à usage unique |
| 1.3 Blocage du vote multiple | `UrneTest::un_electeur_ne_vote_qu_une_fois` (y compris votes simultanés) ; `ExigencesTest::un_second_vote_est_refuse_et_journalise` |
| 2.1 Scrutins paramétrés (dates, uninominal, plurinominal) | `UrneTest::refuse_le_vote_hors_periode`, `vote_blanc_et_plurinominal` ; `CycleEtDepouillementTest::plurinominal_plusieurs_elus` |
| 2.2 Groupes ciblés (classe, promotion, association) | `UrneTest::refuse_un_electeur_d_une_autre_classe` ; `ExigencesTest::un_groupe_ouvre_le_vote_a_des_membres_de_plusieurs_classes` |
| 2.3 Candidatures avec photo, programme, slogan | `PagesTest::les_ecrans_de_l_electeur_et_du_candidat` ; `UrneTest::respecte_le_nombre_de_choix_et_les_candidatures_validees` |
| 3.1–3.3 Vote simple, bouton unique, confirmation | `EcranDeVoteTest::choisir_verifier_deposer_et_recevoir_un_recu` |
| 4.1 Séparation identité / choix | `UrneTest::aucune_colonne_ne_relie_un_bulletin_a_un_electeur` |
| 4.3 Journalisation sans révéler les choix | `ExigencesTest::un_second_vote_est_refuse_et_journalise_sans_rien_dire_du_choix` |
| 5.1–5.3 Résultats immédiats, graphiques, PDF et CSV | `CycleEtDepouillementTest::depouillement_elus_blancs_et_egalite` ; `AccesTest::les_exports_attendent_la_cloture` ; `ChargeTest` |
| 6.1–6.2 Notifications et rappels personnalisés | `CycleEtDepouillementTest::avis_d_ouverture_une_seule_fois_et_rappel_aux_seuls_abstentionnistes` ; `ExigencesTest::les_rappels_suivent_le_choix_de_chaque_electeur`, `l_electeur_choisit_ses_rappels` |
| Affichage des résultats en temps réel ou différé selon paramétrage | `PagesTest::la_participation_en_direct_suit_le_parametrage_du_scrutin` ; `AccesTest::des_resultats_non_publics_restent_reserves` |
| 8.1 Tableau de bord et suivi de la participation | `PagesTest::les_ecrans_de_l_administration` |
| 8.2 Import et export CSV des électeurs | `CycleEtDepouillementTest::import_csv_de_la_scolarite` ; `ExigencesTest::export_de_la_liste_electorale_au_format_d_import` |
| 8.3 Historique de participation et journal | `ExigencesTest::la_liste_d_emargement_dit_qui_a_vote_jamais_quoi`, `les_tentatives_refusees_sont_journalisees_avec_une_ip_tronquee` |
| 9 Intégrité, anti-fraude, journal des tentatives invalides | `UrneTest::l_urne_est_scellee`, `un_bulletin_retire_est_detecte` ; `ExigencesTest::les_tentatives_refusees_sont_journalisees` |
| 9.2 HTTPS et en-têtes de sécurité | `ExigencesTest::choix_du_role_et_en_tetes_de_securite` (HTTPS forcé vérifié au déploiement) |
| 10.1–10.2 Confidentialité, consultation et correction des données | `PagesTest` (pages publiques) ; `ExigencesTest::l_electeur_choisit_ses_rappels` |
| Purge à 30 jours | `CycleEtDepouillementTest::publication_fige_les_chiffres_et_purge_a_trente_jours` |
| Page d’accueil : choix du rôle | `ExigencesTest::choix_du_role_et_en_tetes_de_securite`, `l_administration_se_connecte_par_e_mail` |
| Interfaces bilingues | `PagesTest::le_malagasy_pour_les_electeurs`, `les_notifications_s_affichent_dans_la_langue_choisie` |
| Livrable : guide utilisateur (PDF) | `ExigencesTest::le_guide_d_utilisation_en_page_et_en_pdf` |
| Critère : 500 électeurs, résultats en moins de 2 minutes | `ChargeTest::cinq_cents_electeurs_votent_et_le_depouillement_est_immediat` |
| Pages d’erreur claires, dans la langue choisie | `ExigencesTest::pages_d_erreur_a_la_charte` |
| Photos des candidats identiques sous SQLite et PostgreSQL | `ExigencesTest::une_photo_traverse_la_base_intacte` |
| Avis envoyés en arrière-plan, code de connexion immédiat | `ExigencesTest::les_avis_partent_en_file_d_attente_le_code_de_connexion_tout_de_suite` |

## 3. Liste complète des tests

Accès : un électeur n’entre pas dans l’administration · un visiteur est renvoyé vers la connexion · l’administration va vers son tableau de bord · un scrutin d’une autre classe ou en brouillon est invisible · les résultats ne sont visibles qu’après publication · des résultats non publics restent réservés aux électeurs du scrutin · les exports attendent la clôture · les pages publiques répondent.

Charge : 500 électeurs votent et le dépouillement est immédiat.

Connexion : le code reçu par e-mail ouvre la session · matricule et e-mail doivent désigner le même compte · un compte désactivé ne reçoit pas de code · code faux limité à cinq essais · un code expiré est refusé · un code ne sert qu’une fois · « Essayer sans compte » seulement si la démonstration est active.

Cycle et dépouillement : avis d’ouverture une seule fois et rappel aux seuls abstentionnistes · la publication fige les chiffres, purge à trente jours · élus, blancs et égalité · plurinominal à plusieurs élus · import CSV de la scolarité.

Écran de vote : choisir, vérifier, déposer et recevoir un reçu · en uninominal, un nouveau choix remplace le précédent.

Exigences : tentatives refusées journalisées avec une IP tronquée · second vote refusé et journalisé sans rien dire du choix · export de la liste électorale au format d’import · la liste d’émargement dit qui a voté, jamais quoi, et disparaît après la purge · les rappels suivent le choix de chaque électeur · l’électeur choisit ses rappels · un groupe ouvre le vote à des membres de plusieurs classes · choix du rôle et en-têtes de sécurité · pages d’erreur à la charte (français et malagasy) · une photo traverse la base intacte · les avis partent en file d’attente, le code de connexion tout de suite · guide d’utilisation en page et en PDF · l’administration se connecte par e-mail, un électeur doit donner son matricule.

Pages : écrans de l’administration · écrans de l’électeur et du candidat · résultats publiés et page d’accueil · participation en direct selon le paramétrage · malagasy pour les électeurs, français pour l’administration · notifications dans la langue choisie.

Urne : un électeur vote et reçoit un reçu · un électeur ne vote qu’une fois · aucune colonne ne relie un bulletin à un électeur · refus d’un électeur d’une autre classe ou inactif · refus du vote hors période · respect du nombre de choix et des candidatures validées · vote blanc et plurinominal · l’urne est scellée, toute modification est détectée · un bulletin retiré est détecté.

## 4. Limites des tests

- Le test de charge enchaîne 500 votes dans un seul processus ; il mesure le coût d’un vote et du dépouillement, pas 500 connexions simultanées sur un serveur. La contrainte unique de la base et le verrou de transaction garantissent qu’un vote simultané en double est refusé (testé dans `UrneTest`).
- L’audit axe-core détecte automatiquement une partie des problèmes d’accessibilité ; un test avec un lecteur d’écran (NVDA) et des utilisateurs reste à faire.
- Les critères de succès mesurés en usage réel (participation supérieure à 70 %, satisfaction supérieure à 80 %) ne peuvent être vérifiés qu’après un vrai scrutin.
