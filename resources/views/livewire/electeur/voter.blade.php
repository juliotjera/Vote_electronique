<div>
    <a href="{{ route('espace') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
        <x-icone nom="retour" class="h-4 w-4" /> {{ __('Mes scrutins') }}
    </a>

    <header class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-etat-scrutin :scrutin="$scrutin" />
                <span class="pastille-neutre">{{ $scrutin->libelleType() }}</span>
            </div>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ $scrutin->titre }}</h1>
            @if ($scrutin->description)
                <p class="mt-2 max-w-3xl text-ardoise">{{ $scrutin->description }}</p>
            @endif
        </div>
        <div class="carte shrink-0 px-5 py-4 text-sm">
            @if (in_array($etat, [\App\Enums\EtatScrutin::Ouvert, \App\Enums\EtatScrutin::Programme]))
                @php $ouvert = $etat === \App\Enums\EtatScrutin::Ouvert; @endphp
                <p class="chiffres flex items-center gap-2 font-titre text-base font-semibold text-bleu-700" role="timer"
                    x-data="compteRebours('{{ ($ouvert ? $scrutin->cloture_at : $scrutin->ouverture_at)->toIso8601String() }}', '{{ $ouvert ? 'ferme' : 'ouvre' }}')">
                    <x-icone nom="horloge" class="h-4 w-4" /> <span x-text="texte"></span>
                </p>
            @endif
            <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1">
                <dt class="text-brume">{{ __('Ouverture') }}</dt><dd class="chiffres text-right font-medium">{{ $scrutin->ouverture_at->translatedFormat('j M Y, H\hi') }}</dd>
                <dt class="text-brume">{{ __('Clôture') }}</dt><dd class="chiffres text-right font-medium">{{ $scrutin->cloture_at->translatedFormat('j M Y, H\hi') }}</dd>
            </dl>
        </div>
    </header>

    {{-- ─── Reçu : le moment où l'électeur repart avec sa preuve ─── --}}
    @if ($etape === 'recu')
        <section class="mx-auto mt-10 max-w-xl" aria-labelledby="recu-titre"
            x-data="recuDeVote(@js($recu), @js($scrutin->titre), @js(route('urne', $scrutin)))">
            <div class="fond-nuit relative overflow-hidden rounded-lg px-6 pb-8 pt-10 text-center text-white shadow-feuille sm:px-10">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20"><x-icone nom="urne" class="h-7 w-7 text-bleu-300" /></span>
                <h2 id="recu-titre" tabindex="-1" x-init="$nextTick(() => $el.focus())" class="mt-5 text-3xl font-bold focus:outline-none">{{ __('A voté') }}</h2>
                <p class="mt-2 text-white/70">{{ __('Votre bulletin est dans l’urne, scellé avec les autres. Personne ne peut savoir ce qu’il contient.') }}</p>

                {{-- le ticket --}}
                <div class="relative mt-8 rounded-lg bg-white px-5 py-6 text-encre">
                    <span class="absolute -left-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-bleu-900"></span>
                    <span class="absolute -right-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-bleu-800"></span>
                    <p class="text-xs font-medium text-brume">{{ __('Votre reçu') }}</p>
                    <p class="chiffres mt-2 select-all font-mono text-2xl font-bold tracking-[0.18em] sm:text-3xl">{{ $recu }}</p>
                    <div class="mx-auto mt-4 h-36 w-36 [&_svg]:h-full [&_svg]:w-full" x-html="qr" role="img" aria-label="{{ __('Code QR menant à la vérification de ce reçu') }}"></div>
                    <p class="mt-1 text-xs text-ardoise">{{ __('Scannez-le pour vérifier votre bulletin, à tout moment.') }}</p>
                    <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
                        {{-- dans l'application Android, une image produite par la page ne s'enregistre pas : capture d'écran --}}
                        <button type="button" class="btn-principal min-h-10 px-4" @click="telecharger()" x-show="!dansApplication">
                            <x-icone nom="telecharger" class="h-4 w-4" /> {{ __('Télécharger mon reçu') }}
                        </button>
                        <button type="button" class="btn-contour min-h-10 px-4" @click="copier()">
                            <span x-show="!copie">{{ __('Copier le code') }}</span><span x-show="copie" x-cloak>{{ __('Copié') }}</span>
                        </button>
                    </div>
                    <p x-show="dansApplication" x-cloak class="mt-3 text-sm font-semibold text-bleu-800">{{ __('Faites une capture d’écran de ce reçu, ou copiez son code.') }}</p>
                </div>
                <p class="mt-6 text-sm text-white/70">{{ __('Gardez ce reçu : il ne sera plus jamais affiché. Il permet de vérifier que votre bulletin est compté, sans révéler votre choix.') }}</p>
                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('urne', $scrutin) }}#{{ $recu }}" class="btn-clair">{{ __('Retrouver mon bulletin dans l’urne') }}</a>
                    <a href="{{ route('espace') }}" wire:navigate class="btn inline-flex bg-white text-bleu-900 hover:bg-bleu-50">{{ __('Retour à mes scrutins') }}</a>
                </div>
            </div>
        </section>

    @elseif ($dejaVote)
        <div class="carte mt-8 flex flex-col gap-5 p-6 sm:flex-row sm:items-center">
            <span class="fond-tampon flex h-14 w-14 shrink-0 items-center justify-center rounded-lg text-white"><x-icone nom="coche" class="h-7 w-7" /></span>
            <div class="flex-1">
                <p class="font-titre text-xl font-semibold">{{ $emargement ? __('Vous avez voté le :date', ['date' => $emargement->date_vote->translatedFormat('j F Y')]) : __('Vous avez voté') }}</p>
                <p class="mt-1 text-sm text-ardoise">{{ __('Un électeur, un vote : votre bulletin ne peut plus être modifié. Les résultats seront publiés après la clôture.') }}</p>
            </div>
            <a href="{{ route('urne', $scrutin) }}" class="btn-contour shrink-0"><x-icone nom="urne" class="h-4 w-4" /> {{ __('Retrouver mon bulletin dans l’urne') }}</a>
        </div>

    @elseif ($etat === \App\Enums\EtatScrutin::Ouvert && $etape === 'confirmer')
        {{-- ─── Vérification du bulletin avant dépôt ─── --}}
        <section class="mx-auto mt-10 max-w-xl" aria-labelledby="confirmer-titre">
            <div class="carte p-6 sm:p-8">
                <p class="chiffres text-sm font-medium text-bleu-700">{{ __('Étape 2 sur 2') }}</p>
                <h2 id="confirmer-titre" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())" class="mt-1 text-2xl font-bold focus:outline-none">{{ __('Vérifiez votre bulletin') }}</h2>
                <div class="mt-6 rounded-lg border-2 border-dashed border-bleu-100 bg-bleu-50/50 p-5">
                    @if ($blanc)
                        <p class="font-titre text-lg font-semibold">{{ __('Vote blanc') }}</p>
                        <p class="text-sm text-ardoise">{{ __('Compté dans la participation, sans voix pour un candidat.') }}</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($selection as $candidat)
                                <li class="flex items-center gap-3">
                                    <x-avatar :candidature="$candidat" taille="h-11 w-11 text-sm" />
                                    <div>
                                        <p class="font-semibold">{{ $candidat->user->nomComplet() }}</p>
                                        <p class="text-sm text-ardoise">« {{ $candidat->slogan }} »</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <p class="mt-5 flex gap-2 text-sm text-ardoise"><x-icone nom="cadenas" class="mt-0.5 h-4 w-4" /> {{ __('Une fois déposé, votre bulletin ne peut plus être modifié.') }}</p>
                @error('vote') <p class="erreur">{{ $message }}</p> @enderror
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="modifier" class="btn-contour">{{ __('Modifier') }}</button>
                    <button type="button" wire:click="deposer" wire:loading.attr="disabled" class="btn-principal">
                        <span wire:loading.remove wire:target="deposer">{{ __('Déposer mon bulletin') }}</span>
                        <span wire:loading wire:target="deposer">{{ __('Dépôt dans l’urne…') }}</span>
                    </button>
                </div>
            </div>
        </section>

    @else
        {{-- ─── Bandeau d'état ─── --}}
        @if ($etat === \App\Enums\EtatScrutin::Programme)
            <div class="mt-8 flex flex-wrap items-center gap-4 rounded-lg bg-bleu-50 p-5 ring-1 ring-bleu-100">
                <x-icone nom="calendrier" class="text-bleu-600" />
                <p class="flex-1 text-sm text-bleu-900">{{ __('Le vote ouvrira le :date. Vous recevrez un e-mail ce jour-là.', ['date' => $scrutin->ouverture_at->translatedFormat('l j F, H\hi')]) }}</p>
                @if ($maCandidature)
                    <a href="{{ route('candidater', $scrutin) }}" wire:navigate class="btn-contour">{{ __('Ma candidature') }}</a>
                @elseif ($scrutin->candidaturesOuvertes())
                    <a href="{{ route('candidater', $scrutin) }}" wire:navigate class="btn-principal">{{ __('Me porter candidat') }}</a>
                @endif
            </div>
        @elseif ($etat === \App\Enums\EtatScrutin::Clos)
            <div class="mt-8 flex items-center gap-4 rounded-lg bg-fond p-5 ring-1 ring-trait">
                <x-icone nom="horloge" class="text-ardoise" />
                <p class="text-sm">{{ __('Le vote est clos. Les résultats seront publiés après validation du dépouillement.') }}</p>
            </div>
        @elseif ($etat === \App\Enums\EtatScrutin::Publie)
            <div class="mt-8 flex flex-wrap items-center gap-4 rounded-lg bg-bleu-50 p-5 ring-1 ring-bleu-100">
                <x-icone nom="graphique" class="text-bleu-600" />
                <p class="flex-1 text-sm text-bleu-900">{{ __('Les résultats sont publiés.') }}</p>
                <a href="{{ route('resultats', $scrutin) }}" wire:navigate class="btn-principal">{{ __('Voir les résultats') }}</a>
            </div>
        @endif

        @php $peutVoter = $etat === \App\Enums\EtatScrutin::Ouvert; @endphp
        <section class="mt-8" aria-labelledby="candidats-titre">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <h2 id="candidats-titre" class="text-xl font-bold">
                    @if ($peutVoter)<span class="chiffres text-sm font-medium text-bleu-700">{{ __('Étape 1 sur 2') }}</span><br>@endif
                    {{ $peutVoter ? ($scrutin->nombre_choix > 1 ? __('Choisissez jusqu’à :n candidats', ['n' => $scrutin->nombre_choix]) : __('Choisissez votre candidat')) : __('Les candidats') }}
                </h2>
                <p class="text-sm text-ardoise">{{ trans_choice(':n candidature validée|:n candidatures validées', $candidats->count(), ['n' => $candidats->count()]) }}</p>
            </div>

            @if ($candidats->isEmpty())
                <p class="carte mt-4 p-8 text-center text-ardoise">{{ __('Les candidatures validées apparaîtront ici.') }}</p>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($candidats as $candidat)
                    @php
                        $choisi = in_array($candidat->id, $choix, true);
                        $engagements = $candidat->engagements();
                    @endphp
                    <article @class([
                        'relative flex flex-col rounded-lg border-2 bg-white p-5 transition',
                        'border-bleu-700 bg-bleu-50/40' => $choisi,
                        'border-trait' => ! $choisi,
                    ]) wire:key="candidat-{{ $candidat->id }}">
                        <div class="flex items-center gap-3">
                            <x-avatar :candidature="$candidat" />
                            <div class="min-w-0">
                                <h3 id="candidat-nom-{{ $candidat->id }}" class="truncate text-lg font-bold">{{ $candidat->user->nomComplet() }}</h3>
                                <p class="text-sm text-brume">{{ $candidat->user->classe?->nom }}</p>
                            </div>
                        </div>
                        <p class="mt-4 font-titre text-[17px] font-semibold leading-snug text-bleu-900">« {{ $candidat->slogan }} »</p>
                        <ul class="mt-3 space-y-1.5 text-sm text-ardoise">
                            @foreach (array_slice($engagements, 0, 2) as $engagement)
                                <li class="flex gap-2"><x-icone nom="coche" class="mt-0.5 h-4 w-4 text-bleu-600" /> {{ $engagement }}</li>
                            @endforeach
                        </ul>
                        @if (count($engagements) > 2)
                            <details class="mt-1.5 text-sm text-ardoise">
                                <summary class="cursor-pointer list-none font-medium text-bleu-700 hover:underline">{{ __('Lire la suite (:n)', ['n' => count($engagements) - 2]) }}</summary>
                                <ul class="mt-1.5 space-y-1.5">
                                    @foreach (array_slice($engagements, 2) as $engagement)
                                        <li class="flex gap-2"><x-icone nom="coche" class="mt-0.5 h-4 w-4 text-bleu-600" /> {{ $engagement }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                        @if ($peutVoter)
                            <div class="mt-auto pt-5">
                                <button type="button" wire:click="basculer({{ $candidat->id }})" aria-describedby="candidat-nom-{{ $candidat->id }}" aria-pressed="{{ $choisi ? 'true' : 'false' }}"
                                    @class(['w-full', 'btn-principal' => $choisi, 'btn-contour' => ! $choisi])>
                                    @if ($choisi)<x-icone nom="coche" class="h-4 w-4" /> {{ __('Choisi') }} @else {{ __('Choisir') }} @endif
                                </button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($peutVoter)
                @if ($scrutin->vote_blanc)
                    <button type="button" wire:click="voterBlanc" aria-pressed="{{ $blanc ? 'true' : 'false' }}" @class([
                        'mt-4 flex w-full items-center gap-3 rounded-lg border-2 border-dashed p-4 text-left transition',
                        'border-bleu-600 bg-bleu-50' => $blanc, 'border-trait hover:border-bleu-300' => ! $blanc,
                    ])>
                        <span @class(['flex h-6 w-6 items-center justify-center rounded-md border-2', 'border-bleu-600 bg-bleu-600 text-white' => $blanc, 'border-trait' => ! $blanc])>
                            @if ($blanc)<x-icone nom="coche" class="h-4 w-4" />@endif
                        </span>
                        <span><span class="font-semibold">{{ __('Voter blanc') }}</span> <span class="text-sm text-ardoise">: {{ __('participer sans choisir de candidat') }}</span></span>
                    </button>
                @endif

                {{-- barre d'action collée en bas sur téléphone --}}
                <div class="sticky bottom-0 -mx-4 mt-6 border-t border-trait bg-white/95 px-4 py-4 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0">
                    <div class="flex items-center justify-between gap-4">
                        <p class="chiffres text-sm text-ardoise">{{ $blanc ? __('Vote blanc') : __(':n choix sur :max', ['n' => count($choix), 'max' => $scrutin->nombre_choix]) }}</p>
                        <button type="button" wire:click="verifierBulletin" class="btn-principal" @disabled(! $blanc && ! $choix)>
                            {{ __('Vérifier mon bulletin') }} <x-icone nom="fleche" class="h-4 w-4" />
                        </button>
                    </div>
                    @error('vote') <p class="erreur">{{ $message }}</p> @enderror
                </div>
            @endif
        </section>
    @endif
</div>
