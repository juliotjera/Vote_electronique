<x-layouts::public :title="__('Guide d’utilisation')">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold sm:text-4xl">{{ __('Guide d’utilisation') }}</h1>
                <p class="mt-2 max-w-2xl text-ardoise">{{ __('Voter, vérifier son vote, se porter candidat et organiser un scrutin, étape par étape.') }}</p>
            </div>
            <a href="{{ route('aide.pdf') }}" class="btn-principal"><x-icone nom="telecharger" class="h-4 w-4" /> {{ __('Télécharger le guide (PDF)') }}</a>
        </div>

        <div class="mt-10 grid gap-10 lg:grid-cols-[220px_1fr]">
            <nav aria-label="{{ __('Sommaire du guide') }}" class="lg:sticky lg:top-24 lg:self-start">
                <ul class="flex flex-wrap gap-2 lg:flex-col lg:gap-1">
                    @foreach ($sections as $section)
                        <li><a href="#{{ $section['id'] }}" class="inline-flex min-h-10 items-center rounded-md px-3 text-sm font-semibold text-ardoise ring-1 ring-trait hover:bg-bleu-50 hover:text-bleu-700 lg:ring-0">{{ $section['titre'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div class="space-y-12">
                @foreach ($sections as $section)
                    <section id="{{ $section['id'] }}" class="scroll-mt-24" aria-labelledby="titre-{{ $section['id'] }}">
                        <p class="text-sm font-semibold text-bleu-700">{{ $section['pour'] }}</p>
                        <h2 id="titre-{{ $section['id'] }}" class="mt-1 text-2xl">{{ $section['titre'] }}</h2>
                        <ol class="mt-5 space-y-4">
                            @foreach ($section['etapes'] as $i => [$titre, $texte])
                                <li class="flex gap-4">
                                    <span class="chiffres flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-[1.5px] border-bleu-700 text-sm font-semibold text-bleu-700">{{ $i + 1 }}</span>
                                    <div>
                                        <h3 class="text-base font-bold">{{ $titre }}</h3>
                                        <p class="mt-1 leading-relaxed text-ardoise">{{ $texte }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts::public>
