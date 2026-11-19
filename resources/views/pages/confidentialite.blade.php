<x-layouts::public :title="__('Confidentialité')">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
        <h1 class="text-3xl font-bold sm:text-4xl">{{ __('Politique de confidentialité') }}</h1>
        <p class="mt-3 text-ardoise">{{ __('Ce que la plateforme garde, pourquoi, et pendant combien de temps.') }}</p>

        <div class="mt-10 space-y-8">
            @foreach ([
                [__('Données collectées'), __('Votre matricule, vos nom et prénom, votre adresse e-mail et votre classe, fournis par la scolarité pour établir la liste électorale ; le jour où vous avez voté à un scrutin (liste d’émargement).')],
                [__('Données jamais collectées'), __('Le contenu de votre vote n’est relié à vous d’aucune façon : l’urne ne contient ni votre identifiant, ni l’heure de votre vote, ni votre adresse IP. Le journal d’activité n’enregistre pas les votes.')],
                [__('Votre reçu'), __('Après votre vote, un reçu vous est remis une seule fois. La plateforme n’en garde qu’une empreinte (une transformation à sens unique) : elle ne peut pas le reconstituer, ni le relier à vous. Le QR code du reçu place le code après le signe « # » de l’adresse : cette partie n’est jamais envoyée au serveur.')],
                [__('Durée de conservation'), __('La liste d’émargement d’un scrutin est supprimée automatiquement 30 jours après sa clôture. Les bulletins, anonymes, et les résultats sont conservés. Les codes de connexion expirent après 10 minutes.')],
                [__('Sécurité'), __('Connexion chiffrée (HTTPS), aucun mot de passe stocké (connexion par code à usage unique), protection contre les essais répétés, contrôle d’accès par rôle, journal des actions d’administration et des tentatives refusées, avec une adresse IP tronquée.')],
                [__('Vos droits'), __('Depuis « Mes données », vous consultez ce que la plateforme sait de vous et corrigez votre nom. Pour corriger votre matricule, votre e-mail ou votre classe, adressez-vous à l’administration, qui gère la liste électorale.')],
            ] as [$titre, $texte])
                <section>
                    <h2 class="text-xl font-bold">{{ $titre }}</h2>
                    <p class="mt-2 leading-relaxed text-ardoise">{{ $texte }}</p>
                </section>
            @endforeach
        </div>
    </article>
</x-layouts::public>
