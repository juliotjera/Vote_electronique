<div wire:poll.15s.visible>
    @if ($scrutin)
        @php
            $votants = $scrutin->nombreVotants();
            $inscrits = $scrutin->nombreInscrits();
            $taux = (int) round($scrutin->tauxParticipation());
            $dernier = $derniers->first();
        @endphp
        {{-- La participation en un coup d'œil : un carré par inscrit, bleu dès qu'il a voté (sans dire qui) --}}
        <section class="rounded-xl border border-trait bg-papier p-5 shadow-carte sm:p-6" aria-labelledby="urne-direct-titre">
            <div class="flex items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-bleu-700">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-bleu-500 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-bleu-700"></span></span>
                    {{ __('En direct') }}
                </p>
                <p class="chiffres text-sm text-brume" x-data="compteRebours('{{ $scrutin->cloture_at->toIso8601String() }}')" x-text="texte"></p>
            </div>
            <h2 id="urne-direct-titre" class="mt-3 text-lg">{{ $scrutin->titre }}</h2>

            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="chiffres">
                    <span class="font-titre text-5xl font-bold tracking-tight">{{ $votants }}</span>
                    <span class="ml-1 text-ardoise">{{ __('votes sur :inscrits inscrits', ['inscrits' => $inscrits]) }}</span>
                </p>
                <p class="chiffres font-titre text-2xl font-bold text-bleu-700">{{ $taux }} %</p>
            </div>

            @if ($inscrits > 0 && $inscrits <= 160)
                <div class="mt-4 flex flex-wrap gap-1" aria-hidden="true">
                    @for ($i = 0; $i < $inscrits; $i++)
                        <span @class(['h-3.5 w-3.5 rounded-[3px]', 'bg-bleu-700' => $i < $votants, 'bg-bleu-100' => $i >= $votants])></span>
                    @endfor
                </div>
            @else
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-bleu-100" aria-hidden="true">
                    <div class="h-full rounded-full bg-bleu-700 transition-[width] duration-700" style="width: {{ min(100, $taux) }}%"></div>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-trait pt-4">
                <p class="flex items-center gap-2 text-sm text-ardoise">
                    <x-icone nom="cadenas" class="h-4 w-4 text-bleu-700" />
                    @if ($dernier)
                        {{ __('Dernier bulletin scellé : n° :n', ['n' => $dernier->rang]) }}
                    @else
                        {{ __('Aucun bulletin pour l’instant.') }}
                    @endif
                </p>
                <a href="{{ route('urne', $scrutin) }}" wire:navigate class="text-sm font-semibold text-bleu-700 underline-offset-4 hover:underline">{{ __('Ouvrir l’urne publique') }}</a>
            </div>
        </section>
    @else
        <section class="rounded-xl border border-trait bg-papier p-6 shadow-carte">
            <h2 class="text-lg">{{ __('Aucun vote en cours') }}</h2>
            <p class="mt-1 text-sm text-ardoise">{{ __('Les urnes des scrutins terminés restent consultables avec leurs résultats.') }}</p>
            <a href="{{ route('resultats.publics') }}" wire:navigate class="mt-4 inline-flex text-sm font-semibold text-bleu-700 underline-offset-4 hover:underline">{{ __('Voir les résultats publiés') }}</a>
        </section>
    @endif
</div>
