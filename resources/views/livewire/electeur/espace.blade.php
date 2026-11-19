<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">{{ __('Bonjour :prenom', ['prenom' => $user->prenom]) }}</h1>
            <p class="mt-1 text-ardoise">{{ __('Électeur de la classe :classe', ['classe' => $user->classe?->nom ?? '—']) }} · {{ __('matricule') }} <span class="chiffres">{{ $user->matricule }}</span></p>
        </div>
        <a href="{{ route('verifier') }}" wire:navigate class="btn-contour"><x-icone nom="recu" class="h-4 w-4" /> {{ __('Vérifier un reçu') }}</a>
    </div>

    {{-- Scrutins ouverts où l'électeur n'a pas encore voté : la seule chose urgente de la page --}}
    @foreach ($aVoter as $scrutin)
        <a href="{{ route('scrutin', $scrutin) }}" wire:navigate class="fond-nuit mt-8 block rounded-lg p-6 text-white shadow-feuille transition hover:brightness-110 sm:p-8">
            <div class="flex flex-wrap items-center gap-3">
                <x-etat-scrutin :scrutin="$scrutin" sombre />
                <span class="chiffres inline-flex items-center gap-1.5 text-sm font-semibold text-bleu-300" role="timer"
                    x-data="compteRebours('{{ $scrutin->cloture_at->toIso8601String() }}')">
                    <x-icone nom="horloge" class="h-4 w-4" /> <span x-text="texte"></span>
                </span>
            </div>
            <h2 class="mt-4 text-2xl font-bold sm:text-3xl">{{ $scrutin->titre }}</h2>
            <p class="mt-2 text-white/70">{{ trans_choice(':n candidat|:n candidats', $scrutin->candidatures->count(), ['n' => $scrutin->candidatures->count()]) }} · {{ $scrutin->libelleType() }}</p>
            <span class="mt-6 inline-flex items-center gap-2 rounded-md bg-white px-5 py-3 font-semibold text-bleu-900">
                {{ __('Voter maintenant') }} <x-icone nom="fleche" class="h-4 w-4" />
            </span>
        </a>
    @endforeach

    @if ($aVoter->isEmpty() && $votes->isEmpty() && $aVenir->isEmpty() && $termines->isEmpty())
        <div class="carte mt-8 p-10 text-center">
            <x-icone nom="urne" class="mx-auto h-10 w-10 text-bleu-300" />
            <p class="mt-4 font-titre text-lg font-semibold">{{ __('Aucun scrutin pour votre classe') }}</p>
            <p class="mt-1 text-sm text-ardoise">{{ __('Vous recevrez une notification et un e-mail dès qu’un vote vous concernera.') }}</p>
        </div>
    @endif

    <div class="mt-10 grid gap-8 lg:grid-cols-2">
        @if ($votes->isNotEmpty())
            <section>
                <h2 class="text-lg font-bold">{{ __('Vote enregistré') }}</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($votes as $scrutin)
                        <li class="carte flex items-center gap-4 p-4">
                            <span class="fond-tampon flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"><x-icone nom="coche" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('scrutin', $scrutin) }}" wire:navigate class="font-medium hover:underline">{{ $scrutin->titre }}</a>
                                <p class="text-sm text-ardoise">{{ __('Résultats après la clôture, le :date', ['date' => $scrutin->cloture_at->translatedFormat('j F, H\hi')]) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($aVenir->isNotEmpty())
            <section>
                <h2 class="text-lg font-bold">{{ __('À venir') }}</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($aVenir as $scrutin)
                        <li class="carte p-4">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('scrutin', $scrutin) }}" wire:navigate class="font-medium hover:underline">{{ $scrutin->titre }}</a>
                                <x-etat-scrutin :scrutin="$scrutin" />
                            </div>
                            <p class="chiffres mt-1 text-sm text-ardoise" x-data="compteRebours('{{ $scrutin->ouverture_at->toIso8601String() }}', 'ouvre')" x-text="texte"></p>
                            @if (isset($mesCandidatures[$scrutin->id]))
                                <a href="{{ route('candidater', $scrutin) }}" wire:navigate class="pastille-bleu mt-3">{{ __('Ma candidature') }} : {{ __(\App\Models\Candidature::LIBELLES[$mesCandidatures[$scrutin->id]]) }}</a>
                            @elseif ($scrutin->candidaturesOuvertes())
                                <a href="{{ route('candidater', $scrutin) }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-bleu-700 hover:underline">
                                    {{ __('Me porter candidat') }} <x-icone nom="fleche" class="h-4 w-4" />
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($termines->isNotEmpty())
            <section>
                <h2 class="text-lg font-bold">{{ __('Terminés') }}</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($termines as $scrutin)
                        <li class="carte flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $scrutin->titre }}</p>
                                <p class="text-sm text-ardoise">{{ __('Clos le :date', ['date' => $scrutin->cloture_at->translatedFormat('j F Y')]) }}</p>
                            </div>
                            @if ($scrutin->etat() === \App\Enums\EtatScrutin::Publie)
                                <a href="{{ route('resultats', $scrutin) }}" wire:navigate class="btn-contour min-h-9 shrink-0 px-3"><x-icone nom="graphique" class="h-4 w-4" /> {{ __('Résultats') }}</a>
                            @else
                                <x-etat-scrutin :scrutin="$scrutin" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
