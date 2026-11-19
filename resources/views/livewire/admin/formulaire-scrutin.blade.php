<div class="mx-auto max-w-3xl">
    <a href="{{ $scrutin ? route('admin.scrutins.suivi', $scrutin) : route('admin.scrutins') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
        <x-icone nom="retour" class="h-4 w-4" /> {{ $scrutin ? $scrutin->titre : 'Scrutins' }}
    </a>
    <h1 class="mt-4 text-3xl font-bold">{{ $scrutin ? 'Modifier le scrutin' : 'Nouveau scrutin' }}</h1>
    <p class="mt-1 text-ardoise">Enregistré en brouillon : rien n’est visible des électeurs avant la programmation.</p>

    <form wire:submit="enregistrer" class="mt-6 space-y-6">
        <section class="carte space-y-4 p-6">
            <h2 class="text-lg font-bold">Le scrutin</h2>
            <div>
                <label for="titre" class="libelle">Intitulé</label>
                <input id="titre" wire:model="titre" class="champ" placeholder="Élection des délégués L2 2026" required>
                @error('titre') <p class="erreur">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="libelle">Description <span class="font-normal text-brume">(facultative)</span></label>
                <textarea id="description" wire:model="description" rows="3" class="champ" placeholder="Rôle des élus, durée du mandat…"></textarea>
            </div>
        </section>

        <section class="carte space-y-4 p-6">
            <h2 class="text-lg font-bold">Mode de scrutin</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (['uninominal' => ['Uninominal', 'Un seul nom sur le bulletin.'], 'plurinominal' => ['Plurinominal', 'Plusieurs noms, jusqu’à une limite.']] as $valeur => [$libelle, $aide])
                    <label @class(['cursor-pointer rounded-md border-2 p-4 transition', 'border-bleu-600 bg-bleu-50' => $type === $valeur, 'border-trait hover:border-bleu-300' => $type !== $valeur])>
                        <input type="radio" wire:model.live="type" value="{{ $valeur }}" class="sr-only">
                        <span class="font-semibold">{{ $libelle }}</span>
                        <span class="mt-0.5 block text-sm text-ardoise">{{ $aide }}</span>
                    </label>
                @endforeach
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($type === 'plurinominal')
                    <div>
                        <label for="nombre_choix" class="libelle">Choix maximum par bulletin</label>
                        <input id="nombre_choix" type="number" min="1" max="20" wire:model="nombre_choix" class="champ chiffres">
                        @error('nombre_choix') <p class="erreur">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label for="nombre_elus" class="libelle">Nombre d’élus</label>
                    <input id="nombre_elus" type="number" min="1" max="20" wire:model="nombre_elus" class="champ chiffres">
                    @error('nombre_elus') <p class="erreur">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" wire:model="vote_blanc" class="h-4 w-4 accent-bleu-600"> Autoriser le vote blanc</label>
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" wire:model="resultats_publics" class="h-4 w-4 accent-bleu-600"> Publier la participation en direct et les résultats sur la page publique (sinon, visibles des seuls électeurs du scrutin)</label>
        </section>

        <section class="carte space-y-4 p-6">
            <h2 class="text-lg font-bold">Calendrier</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="candidatures_fin_at" class="libelle">Fin des candidatures</label>
                    <input id="candidatures_fin_at" type="datetime-local" wire:model="candidatures_fin_at" class="champ">
                    @error('candidatures_fin_at') <p class="erreur">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ouverture_at" class="libelle">Ouverture du vote</label>
                    <input id="ouverture_at" type="datetime-local" wire:model="ouverture_at" class="champ" required>
                    @error('ouverture_at') <p class="erreur">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cloture_at" class="libelle">Clôture</label>
                    <input id="cloture_at" type="datetime-local" wire:model="cloture_at" class="champ" required>
                    @error('cloture_at') <p class="erreur">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-brume">Heure de Madagascar. Les électeurs reçoivent un e-mail à l’ouverture et, s’ils n’ont pas voté, un rappel au moment qu’ils ont choisi (la veille ou 3 h avant la clôture).</p>
        </section>

        <section class="carte space-y-3 p-6">
            <h2 class="text-lg font-bold">Liste électorale</h2>
            <p class="text-sm text-ardoise">Seuls les électeurs actifs des classes ou des groupes cochés pourront voter et se porter candidats.</p>
            <h3 class="pt-1 text-sm font-semibold">Classes et promotions</h3>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($toutesClasses as $classe)
                    <label @class(['flex cursor-pointer items-center justify-between gap-2 rounded-md border px-4 py-3 text-sm', 'border-bleu-600 bg-bleu-50' => in_array($classe->id, $classes), 'border-trait' => ! in_array($classe->id, $classes)])>
                        <span class="flex items-center gap-2"><input type="checkbox" wire:model.live="classes" value="{{ $classe->id }}" class="h-4 w-4 accent-bleu-600"> {{ $classe->nom }}</span>
                        <span class="chiffres text-xs text-brume">{{ $classe->electeurs_count }}</span>
                    </label>
                @endforeach
            </div>
            @if ($tousGroupes->isNotEmpty())
                <h3 class="pt-2 text-sm font-semibold">Associations, clubs et comités</h3>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($tousGroupes as $groupe)
                        <label @class(['flex cursor-pointer items-center justify-between gap-2 rounded-md border px-4 py-3 text-sm', 'border-bleu-600 bg-bleu-50' => in_array($groupe->id, $groupes), 'border-trait' => ! in_array($groupe->id, $groupes)])>
                            <span class="flex items-center gap-2"><input type="checkbox" wire:model.live="groupes" value="{{ $groupe->id }}" class="h-4 w-4 accent-bleu-600"> {{ $groupe->nom }}</span>
                            <span class="chiffres text-xs text-brume">{{ $groupe->membres_count }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            <p class="text-xs text-brume"><a href="{{ route('admin.groupes') }}" wire:navigate class="font-semibold text-bleu-700 underline underline-offset-2">Gérer les groupes</a> : associations, clubs, comités dont les membres viennent de plusieurs classes.</p>
            @error('classes') <p class="erreur">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-principal" wire:loading.attr="disabled">Enregistrer le brouillon</button>
        </div>
    </form>
</div>
