<div class="fond-nuit min-h-[calc(100vh-4rem)]">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_440px] lg:items-center lg:py-20">
        {{-- Ce qui se passe quand on se connecte --}}
        <div class="order-2 text-white lg:order-1">
            <h1 class="text-3xl font-bold leading-tight sm:text-[42px]">{{ __('Votre identité est vérifiée.') }}<br>{{ __('Votre choix, jamais.') }}</h1>
            <p class="mt-4 max-w-lg text-white/70">{{ __('Pas de mot de passe à retenir : votre matricule et votre e-mail suffisent. Un code à usage unique vous est envoyé pour confirmer que c’est bien vous.') }}</p>
            <ol class="mt-8 space-y-4">
                @foreach ([
                    ['cle', __('Matricule et e-mail'), __('tels qu’inscrits sur la liste électorale de votre établissement.')],
                    ['courrier', __('Code à 6 chiffres'), __('valable 10 minutes, il ne sert qu’une fois.')],
                    $espace === 'administration'
                        ? ['tableau', __('Votre tableau de bord'), __('scrutins, candidatures, listes électorales et résultats.')]
                        : ['urne', __('Votre espace de vote'), __('les scrutins de votre classe, un bulletin par scrutin.')],
                ] as $i => [$icone, $titre, $texte])
                    <li class="flex gap-4">
                        <span class="chiffres flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/10 text-sm font-semibold ring-1 ring-white/15">{{ $i + 1 }}</span>
                        <p class="pt-1.5 text-sm text-white/70"><span class="font-semibold text-white">{{ $titre }}</span> : {{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Formulaire --}}
        <div class="carte order-1 p-6 shadow-feuille sm:p-8 lg:order-2">
            @if ($etape === 'identite')
                {{-- choix du rôle --}}
                <div class="mb-6 grid grid-cols-2 gap-1 rounded-lg bg-fond p-1 ring-1 ring-trait" role="group" aria-label="{{ __('Votre espace') }}">
                    @foreach (['electeur' => __('Électeur'), 'administration' => __('Administration')] as $valeur => $libelle)
                        <button type="button" wire:click="$set('espace', '{{ $valeur }}')" aria-pressed="{{ $espace === $valeur ? 'true' : 'false' }}"
                            @class(['min-h-10 rounded-md text-sm font-semibold transition', 'bg-papier text-bleu-700 shadow-carte ring-1 ring-trait' => $espace === $valeur, 'text-ardoise hover:text-encre' => $espace !== $valeur])>{{ $libelle }}</button>
                    @endforeach
                </div>
                <h2 class="text-2xl font-bold">{{ $espace === 'administration' ? __('Espace administration') : __('Espace électeur') }}</h2>
                <p class="mt-1 text-sm text-ardoise">{{ $espace === 'administration' ? __('Service de la vie étudiante et BDE : organisez les scrutins et publiez les résultats.') : __('Étudiants et candidats : votez et suivez vos scrutins.') }}</p>
                <form wire:submit="demander" class="mt-6 space-y-4">
                    @if ($espace !== 'administration')
                    <div>
                        <label for="matricule" class="libelle">{{ __('Matricule') }}</label>
                        <input id="matricule" wire:model="matricule" class="champ chiffres" autocomplete="username" inputmode="numeric" placeholder="20230001" required>
                        @error('matricule') <p class="erreur">{{ $message }}</p> @enderror
                    </div>
                    @endif
                    <div>
                        <label for="email" class="libelle">{{ __('Adresse e-mail') }}</label>
                        <input id="email" type="email" wire:model="email" class="champ" autocomplete="email" placeholder="prenom.nom@univ-mg.edu" required>
                        @error('email') <p class="erreur">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-principal w-full text-base" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="demander">{{ __('Recevoir mon code') }}</span>
                        <span wire:loading wire:target="demander">{{ __('Envoi du code…') }}</span>
                    </button>
                </form>
            @else
                <button type="button" wire:click="recommencer" class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
                    <x-icone nom="retour" class="h-4 w-4" /> {{ __('Modifier mon identité') }}
                </button>
                <h2 class="mt-4 text-2xl font-bold">{{ __('Entrez votre code') }}</h2>
                <p class="mt-1 text-sm text-ardoise">{{ __('Envoyé à :email. Pensez à regarder dans les courriers indésirables.', ['email' => $email]) }}</p>
                @if ($codeDeveloppement)
                    <p class="mt-4 rounded-md bg-bleu-50 px-4 py-3 text-sm text-bleu-900 ring-1 ring-bleu-100">
                        {{ __('Développement : le code envoyé est') }} <span class="chiffres font-semibold">{{ $codeDeveloppement }}</span>
                    </p>
                @endif
                <form wire:submit="verifier" class="mt-6 space-y-4">
                    <div>
                        <label for="code" class="libelle">{{ __('Code à 6 chiffres') }}</label>
                        <input id="code" wire:model="code" class="champ chiffres text-center text-2xl tracking-[0.4em]" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus>
                        @error('code') <p class="erreur">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-principal w-full text-base" wire:loading.attr="disabled">{{ __('Se connecter') }}</button>
                    <button type="button" wire:click="demander" class="min-h-10 w-full text-center text-sm text-bleu-700 hover:underline">{{ __('Renvoyer un code') }}</button>
                </form>
            @endif

            @if ($demo)
                <div class="mt-7 border-t border-trait pt-5">
                    <p class="text-sm font-medium">{{ __('Essayer sans compte') }}</p>
                    <p class="mt-0.5 text-xs text-ardoise">{{ __('Ouvrez un espace avec les données de démonstration.') }}</p>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach (['electeur' => __('Électeur'), 'candidat' => __('Candidat'), 'admin' => __('Administration')] as $role => $libelle)
                            <button type="button" wire:click="essayer('{{ $role }}')" wire:loading.attr="disabled" aria-label="{{ __('Essayer en tant que :role', ['role' => mb_strtolower($libelle)]) }}"
                                class="min-h-11 rounded-md border border-trait px-1 py-2.5 text-[13px] font-medium hover:border-bleu-300 hover:bg-bleu-50 sm:px-2 sm:text-sm">
                                {{ $libelle }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
