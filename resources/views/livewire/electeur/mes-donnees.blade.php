<div class="mx-auto max-w-3xl">
    <h1 class="text-3xl font-bold">{{ __('Mes données') }}</h1>
    <p class="mt-1 text-ardoise">{{ __('Ce que la plateforme sait de vous, et rien de plus.') }} <a href="{{ route('confidentialite') }}" wire:navigate class="text-bleu-700 underline underline-offset-2 hover:decoration-2">{{ __('Politique de confidentialité') }}</a></p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1fr]">
        <form wire:submit="enregistrer" class="carte space-y-4 p-6">
            <h2 class="text-lg font-bold">{{ __('Identité') }}</h2>
            <div>
                <label for="prenom" class="libelle">{{ __('Prénom') }}</label>
                <input id="prenom" wire:model="prenom" class="champ" required>
                @error('prenom') <p class="erreur">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="nom" class="libelle">{{ __('Nom') }}</label>
                <input id="nom" wire:model="nom" class="champ" required>
                @error('nom') <p class="erreur">{{ $message }}</p> @enderror
            </div>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 border-t border-trait pt-4 text-sm">
                <dt class="text-brume">{{ __('Matricule') }}</dt><dd class="chiffres font-medium">{{ $user->matricule }}</dd>
                <dt class="text-brume">{{ __('E-mail') }}</dt><dd class="break-all font-medium">{{ $user->email }}</dd>
                <dt class="text-brume">{{ __('Classe') }}</dt><dd class="font-medium">{{ $user->classe?->nom ?? '—' }}</dd>
            </dl>
            <p class="text-xs text-brume">{{ __('Matricule, e-mail ou classe incorrects ? Ils viennent de la liste électorale : demandez la correction à l’administration.') }}</p>
            <button type="submit" class="btn-principal w-full">{{ __('Enregistrer') }}</button>
        </form>

        <section class="carte p-6">
            <h2 class="text-lg font-bold">{{ __('Ma participation') }}</h2>
            <p class="mt-1 text-sm text-ardoise">{{ __('La liste d’émargement garde le jour de votre vote, jamais votre choix. Elle est supprimée 30 jours après la clôture du scrutin.') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($participations as $p)
                    <li class="flex items-center justify-between gap-3 rounded-md bg-fond px-3 py-2.5">
                        <span class="font-medium">{{ $p->scrutin?->titre }}</span>
                        <span class="chiffres text-brume">{{ $p->date_vote->translatedFormat('j M Y') }}</span>
                    </li>
                @empty
                    <li class="text-ardoise">{{ __('Aucun vote enregistré.') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- rappels personnalisés --}}
        <form wire:submit="enregistrerRappels" class="carte p-6 lg:col-span-2">
            <h2 class="text-lg font-bold">{{ __('Mes rappels') }}</h2>
            <p class="mt-1 text-sm text-ardoise">{{ __('Si vous n’avez pas encore voté, quand voulez-vous être prévenu avant la clôture ? L’avis apparaît toujours dans la cloche ; ce choix concerne l’e-mail.') }}</p>
            <fieldset class="mt-4">
                <legend class="sr-only">{{ __('Moment du rappel') }}</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach (['veille' => [__('La veille'), __('24 h avant la clôture')], 'jour' => [__('Le jour même'), __('3 h avant la clôture')], 'aucun' => [__('Pas d’e-mail'), __('Dans la cloche seulement')]] as $valeur => [$titre, $detail])
                        <label @class(['flex min-h-16 cursor-pointer items-start gap-3 rounded-lg border p-3.5 transition', 'border-bleu-700 bg-bleu-50/60 ring-1 ring-bleu-700' => $rappel === $valeur, 'border-trait hover:border-trait-fort' => $rappel !== $valeur])>
                            <input type="radio" wire:model.live="rappel" value="{{ $valeur }}" class="mt-1 h-4 w-4 accent-bleu-700">
                            <span><span class="block font-semibold">{{ $titre }}</span><span class="block text-sm text-brume">{{ $detail }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="mt-4 flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="avisEmail" class="mt-0.5 h-5 w-5 accent-bleu-700">
                <span>{{ __('Recevoir aussi par e-mail l’ouverture des votes et la publication des résultats.') }}</span>
            </label>
            @error('rappel') <p class="erreur">{{ $message }}</p> @enderror
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-principal">{{ __('Enregistrer mes rappels') }}</button>
                @if (session('succes_rappels'))<p class="text-sm font-semibold text-bleu-700" role="status">{{ session('succes_rappels') }}</p>@endif
            </div>
        </form>
    </div>
</div>
