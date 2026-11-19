<x-layouts::public :title="__('Résultats')">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16">
        <h1 class="text-3xl font-bold sm:text-4xl">{{ __('Résultats publiés') }}</h1>
        <p class="mt-2 text-ardoise">{{ __('Scrutins clos dont l’organisation a rendu les résultats publics.') }}</p>
        <ul class="mt-8 space-y-3">
            @forelse ($scrutins as $scrutin)
                <li>
                    <a href="{{ route('resultats', $scrutin) }}" wire:navigate class="carte flex items-center gap-4 p-5 transition hover:border-bleu-300">
                        <span class="fond-tampon flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-white"><x-icone nom="graphique" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $scrutin->titre }}</span>
                            <span class="block text-sm text-ardoise">{{ $scrutin->publicCible() }} · {{ __('Clos le :date', ['date' => $scrutin->cloture_at->translatedFormat('j F Y')]) }} · {{ __('participation') }} {{ number_format($scrutin->tauxParticipation(), 1, ',', ' ') }} %</span>
                        </span>
                        <x-icone nom="fleche" class="h-4 w-4 text-brume" />
                    </a>
                </li>
            @empty
                <li class="carte p-10 text-center text-ardoise">{{ __('Aucun résultat publié pour l’instant.') }}</li>
            @endforelse
        </ul>
    </div>
</x-layouts::public>
