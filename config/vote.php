<?php

return [
    // Boutons « Essayer sans compte » (site de démonstration) : connexion en un clic à un compte
    // de démonstration, sans code. Activés en développement ; en ligne avec COMPTES_DEMO=true.
    'comptes_demo' => env('COMPTES_DEMO', env('APP_ENV') === 'local'),

    // Site de démonstration en ligne : recrée les données fictives quand le vote de démonstration est clos,
    // pour qu'un visiteur trouve toujours un scrutin ouvert. JAMAIS sur une plateforme utilisée pour de vrais votes.
    'demo_reinitialisation' => (bool) env('DEMO_REINITIALISATION', false),
];
