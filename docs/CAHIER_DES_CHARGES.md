# Cahier des charges — Application de vote électronique (version améliorée)

Reprise du cahier des charges de Licence 1 (« Application de vote électronique pour les délégués »), corrigé et complété pour la version Laravel. Les numéros permettent de citer chaque exigence.

## 1. Contexte et objectif

Les élections internes (délégués de classe, BDE, représentants) se font sur bulletin papier : participation limitée aux présents, dépouillement long, soupçons de fraude (bulletins glissés en plus, listes d’absents remplies par d’autres), affiches arrachées. Un essai avec Google Forms a échoué : n’importe qui pouvait revoter ou transmettre le lien.

**Objectif** : une application web où chaque électeur authentifié vote **une seule fois**, **en secret**, peut **vérifier** que son vote est compté, et où les résultats sont **calculés automatiquement** à la clôture.

## 2. Parties prenantes

2.1. **Électeurs** : étudiants inscrits sur la liste électorale d’une classe.
2.2. **Candidats** : électeurs qui déposent une candidature à un scrutin.
2.3. **Administration** : service de la vie étudiante, BDE ; organise les scrutins, valide les candidatures, publie les résultats.
2.4. **Public** : consulte les résultats publiés et vérifie un reçu.

## 3. Besoins exprimés et réponse apportée

| # | Besoin (entretiens) | Réponse |
| --- | --- | --- |
| 3.1 | Voter sans être sur le campus | Application web adaptée au téléphone |
| 3.2 | Personne ne glisse plusieurs bulletins | Émargement unique par scrutin, garanti par la base |
| 3.3 | Authentification fiable : un vote par personne | Matricule + e-mail + code à usage unique envoyé par e-mail |
| 3.4 | Simple : « un bouton voter et basta » | Trois écrans : choisir, vérifier, déposer |
| 3.5 | Anonyme mais fiable | Urne séparée de l’émargement ; reçu vérifiable |
| 3.6 | Résultats rapides avec graphiques | Dépouillement automatique, barres de résultats, PDF |
| 3.7 | Alerte d’ouverture et de clôture | E-mails et notifications ; rappel 24 h avant la clôture |
| 3.8 | Candidatures en ligne (photo, programme) | Dépôt en ligne, validation par l’administration |
| 3.9 | Le vote n’est pas associé au nom | Aucune colonne commune entre électeur et bulletin |
| 3.10 | Réutilisable pour d’autres votes | Scrutins paramétrables (classes, mode, nombre d’élus) |
| 3.11 | Réseau lent | Pages légères rendues par le serveur, sans bibliothèque lourde |
| 3.12 | Tout se fait au téléphone | Conception mobile d’abord |
| 3.13 | Vote ouvert deux ou trois jours | Dates d’ouverture et de clôture libres (trois jours proposés par défaut) |
| 3.14 | Empêcher de voter à la place des absents | Code envoyé sur l’e-mail personnel de l’électeur |
| 3.15 | Lien partagé, revote possible (Google Forms) | Accès réservé aux électeurs de la liste ; un vote par scrutin |

## 4. Fonctionnalités

### 4.1. Authentification
4.1.1. Connexion par matricule + e-mail, puis code à 6 chiffres reçu par e-mail.
4.1.2. Code valable 10 minutes, à usage unique, 5 essais au maximum ; seule son empreinte est stockée.
4.1.3. Limitation des demandes de code par adresse IP ; même message d’erreur que le compte existe ou non.
4.1.4. Un compte désactivé ne peut ni se connecter ni voter ; sa session en cours est fermée.
4.1.5. Choix du rôle dès l’accueil et sur la page de connexion : espace électeur (matricule + e-mail) ou espace administration (e-mail seul). Le rôle réel vient toujours du compte.

### 4.2. Scrutins
4.2.1. Création en brouillon : intitulé, description, mode (uninominal ou plurinominal), nombre de choix, nombre d’élus, vote blanc, fin des candidatures, ouverture, clôture, classes et groupes concernés, résultats publics ou non.
4.2.2. Programmation : le scrutin est annoncé aux électeurs et ne se modifie plus.
4.2.3. État déduit des dates : annoncé, ouvert, clos ; l’ouverture et la clôture ne dépendent d’aucune tâche planifiée.
4.2.4. Publication des résultats par l’administration après la clôture.

### 4.3. Candidatures
4.3.1. Un électeur du scrutin dépose une candidature (photo facultative, slogan, programme) jusqu’à la date limite.
4.3.2. Validation ou refus motivé par l’administration, avant l’ouverture ; le candidat est prévenu.
4.3.3. Une candidature refusée peut être corrigée et renvoyée ; elle peut être retirée tant que le dépôt est ouvert.

### 4.4. Vote
4.4.1. L’électeur choisit un candidat (ou jusqu’à N en plurinominal) ou vote blanc.
4.4.2. Son bulletin lui est présenté une dernière fois avant le dépôt.
4.4.3. Après le dépôt, il reçoit un **reçu** de 12 caractères, affiché une seule fois.
4.4.4. Toute personne peut vérifier un reçu : la page indique si le bulletin est dans l’urne et si l’urne est intègre, jamais son contenu.
4.4.5. **Urne publique** : chaque scrutin publie la liste de ses bulletins (numéro, empreinte du reçu, empreinte du bulletin), sans contenu. L’électeur y retrouve son bulletin avec son reçu ; le calcul est fait dans le navigateur, le reçu n’est jamais envoyé.

### 4.5. Résultats
4.5.1. Pendant le vote, seule la participation est visible : toujours pour l’administration ; en temps réel sur la page publique si le scrutin est paramétré « résultats publics » (sujet de L1 : « affichage en temps réel ou différé selon paramétrage »).
4.5.2. À la clôture : inscrits, votants, participation, blancs, voix et pourcentage par candidat, élus ; égalité signalée pour le dernier siège.
4.5.3. Procès-verbal PDF et export CSV (compatible Excel).
4.5.5. Liste d’émargement consultable et exportable par l’administration : matricule, nom, classe et jour du vote, jamais le choix ; triée par nom, jamais dans l’ordre des votes.
4.5.4. Résultats visibles des électeurs du scrutin après publication ; sur la page publique si l’administration l’a choisi.

### 4.6. Liste électorale
4.6.1. Import CSV au format de la scolarité (`Matricule,Nom,Prénom,Email,Statut,Classe`, virgule ou point-virgule) ; mise à jour par matricule ; classes créées si besoin ; erreurs signalées ligne par ligne.
4.6.2. Recherche, filtre par classe, activation et désactivation d’un électeur.
4.6.3. Export CSV de la liste électorale au format d’import (réimportable tel quel).
4.6.4. Groupes (associations, clubs, comités) : un électeur peut être membre de plusieurs groupes en plus de sa classe ; membres saisis par matricule.

### 4.7. Notifications
4.7.1. Ouverture du vote, rappel 24 h avant la clôture (aux seuls électeurs qui n’ont pas voté), résultats publiés, décision sur une candidature.
4.7.2. Par e-mail et dans l’application.
4.7.3. Rappels personnalisés : chaque électeur choisit d’être rappelé la veille (24 h avant la clôture, par défaut), le jour même (3 h avant) ou seulement dans l’application ; il choisit aussi de recevoir ou non l’ouverture et les résultats par e-mail.

### 4.8. Données personnelles
4.8.1. L’électeur consulte ses données et corrige son nom ; le reste passe par l’administration.
4.8.2. La liste d’émargement d’un scrutin est supprimée automatiquement 30 jours après la clôture ; les chiffres de participation sont figés avant.

## 5. Règles de gestion

5.1. Un électeur vote au plus une fois par scrutin (contrainte unique `emargements(scrutin_id, user_id)`).
5.2. Seul un électeur actif d’une classe concernée peut voter ou candidater.
5.3. Un bulletin contient soit un vote blanc, soit 1 à `nombre_choix` candidatures **validées** du scrutin.
5.4. Le vote n’est possible qu’entre l’ouverture et la clôture.
5.5. Un scrutin programmé ne se modifie plus ; seul un brouillon se supprime.
5.6. Les élus sont les `nombre_elus` premiers ; en cas d’égalité pour le dernier siège, aucun élu n’est proclamé pour ce siège.
5.7. Les pourcentages sont calculés sur les bulletins exprimés (hors blancs).

## 6. Modèle de données

### 6.1. MLD

```
classes          (id, nom, description)
users            (id, matricule, nom, prenom, email, role, #classe_id, actif, demo, derniere_connexion_at)
codes_connexion  (id, #user_id, empreinte, tentatives, expire_at, utilise_at)
scrutins         (id, titre, description, type, nombre_choix, nombre_elus, vote_blanc, candidatures_fin_at,
                  ouverture_at, cloture_at, resultats_publics, programme_at, publie_at,
                  inscrits_cloture, votants_cloture, notif_*, purge_at, #cree_par)
classe_scrutin   (#scrutin_id, #classe_id)
photos           (id, type, contenu)
candidatures     (id, #scrutin_id, #user_id, slogan, programme, #photo_id, statut, motif_rejet, #decide_par, decide_at)
emargements      (id UUID, #scrutin_id, #user_id, date_vote)            -- QUI a voté
bulletins        (id UUID, #scrutin_id, rang, blanc, recu_empreinte, empreinte)   -- CE qui a été voté
bulletin_choix   (#bulletin_id, #candidature_id)
journal          (id, #user_id, action, details, ip, created_at)
```

### 6.2. Correction par rapport au modèle de L1

La table `votes(scrutin_id, electeur_id, candidat_id, date_vote, ip_address)` reliait directement l’électeur à son choix, avec l’heure et l’adresse IP : le vote n’était pas secret. Elle est remplacée par `emargements` + `bulletins`, qui ne partagent aucune colonne. La colonne `a_vote` de `utilisateurs` (qui ne permettait qu’un seul scrutin) disparaît.

## 7. Sécurité

7.1. HTTPS en production ; jetons CSRF ; protection XSS par l’échappement Blade ; requêtes préparées (Eloquent).
7.2. Contrôle d’accès par rôle (électeur, administration) et par liste électorale.
7.3. Limitation des tentatives (codes, vérification de reçus, démonstration).
7.4. Urne scellée par chaîne d’empreintes SHA-256 ; vérification affichée sur les résultats et le procès-verbal.
7.5. Journal d’activité des actions d’administration et des tentatives refusées (connexion, code, accès, vote en double), sans aucun vote ; adresse IP tronquée (réseau seulement).
7.6. En-têtes de sécurité (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS en production) ; sessions chiffrées.

## 8. Exigences graphiques

8.1. Bleu, blanc et noir uniquement, en dégradés : bleu foncé vers bleu clair pour l’identité (logo, sections sombres, bouton principal, reçu), noir vers gris foncé pour l’administration, blanc pour les fonds. Dégradés discrets, entre teintes voisines ; jamais de texte en dégradé ni de halo.
8.2. Palette (contrastes WCAG AA vérifiés) : bleu d’action `#1D4ED8` (6,7:1 sur blanc), marine `#0B1F4D`, texte `#0F172A` / `#334155` / `#586679`, fonds `#F6F8FB`, traits `#E2E8F0`. Le rouge (`#B91C1C`) est la seule exception au sujet : il est réservé aux messages d’erreur, pour qu’une erreur ne se distingue pas par la seule forme.
8.3. Une seule police sans empattement, Plus Jakarta Sans, pour les titres et le texte (uniformité demandée par le sujet) ; IBM Plex Mono pour les codes de reçu et les empreintes. Polices hébergées avec l’application.
8.4. Formes arrondies, icônes monochromes au trait, résultats en anneau (participation) et en barres (voix par candidat).
8.5. Mobile d’abord ; barre d’action fixée en bas de l’écran de vote sur téléphone ; cibles tactiles d’au moins 24 px (WCAG 2.2).
8.6. Accessibilité : WCAG 2.2 niveau AA (audit axe-core : 0 erreur sur les 18 écrans) ; navigation complète au clavier, focus visible, focus déplacé à chaque étape du vote.

## 9. Arborescence

```
Public          Accueil · Résultats publiés · Résultat d'un scrutin · Urne publique · Vérifier un reçu · Connexion · Confidentialité
Électeur        Mes scrutins · Scrutin (candidats, vote, reçu) · Ma candidature · Notifications · Mes données
Public (suite)  Guide d'utilisation (page et PDF)
Administration  Tableau de bord · Scrutins (liste, création, suivi, émargement, PDF, CSV) · Candidatures · Électeurs (import, export) · Groupes · Journal
```

## 10. Critères d’acceptation

10.1. Un second vote du même électeur est refusé, même envoyé en même temps (test automatisé).
10.2. Aucune colonne des tables de l’urne ne permet de retrouver l’électeur (test automatisé).
10.3. Un bulletin modifié ou retiré en base est détecté (test automatisé).
10.4. Les résultats sont disponibles dès la clôture, sans dépouillement manuel.
10.5. Toutes les pages s’affichent sur un téléphone de 375 px de large.
10.6. 500 électeurs votent et les résultats sont disponibles en moins de 2 minutes après la clôture (test de charge : dépouillement en 0,1 s).

## 10 bis. Livrables

| Livrable du sujet | Où |
| --- | --- |
| Code source | dépôt Git du projet |
| Guide d’installation | [`INSTALLATION.md`](INSTALLATION.md) |
| Guide utilisateur (PDF) | [`GUIDE_UTILISATEUR.pdf`](GUIDE_UTILISATEUR.pdf), et la page « Guide d’utilisation » de l’application |
| Rapport de tests fonctionnels | [`RAPPORT_DE_TESTS.md`](RAPPORT_DE_TESTS.md) |
| Application déployée | à faire (Render + Neon) |

## 11. Limites connues

11.1. Un administrateur de la base de données peut toujours effacer l’urne entière et la recalculer : la chaîne d’empreintes détecte les retouches, pas une reconstruction complète.
11.2. Les journaux du serveur web gardent l’heure des requêtes.
11.3. Un scrutin officiel demanderait un chiffrement de bout en bout et des autorités de dépouillement indépendantes.
