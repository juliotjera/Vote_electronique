<div>
    <h1 class="text-3xl font-bold">Groupes</h1>
    <p class="mt-1 max-w-3xl text-ardoise">Associations, clubs et comités dont les membres viennent de plusieurs classes. Un scrutin peut viser des classes, des groupes, ou les deux.</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <section aria-labelledby="liste-groupes">
            <h2 id="liste-groupes" class="sr-only">Liste des groupes</h2>
            <ul class="space-y-3">
                @forelse ($groupes as $groupe)
                    <li class="carte" wire:key="groupe-{{ $groupe->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $groupe->nom }}</p>
                                <p class="text-sm text-ardoise">
                                    <span class="chiffres">{{ $groupe->membres_count }}</span> {{ $groupe->membres_count > 1 ? 'membres' : 'membre' }}
                                    @if ($groupe->description) <span class="text-brume">— {{ $groupe->description }}</span>@endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="ouvrir({{ $groupe->id }})" aria-expanded="{{ $ouvert === $groupe->id ? 'true' : 'false' }}" class="btn-contour min-h-10 px-4">{{ $ouvert === $groupe->id ? 'Fermer' : 'Membres' }}</button>
                                @if (! $groupe->scrutins_count)
                                    <button type="button" wire:click="supprimer({{ $groupe->id }})" wire:confirm="Supprimer le groupe « {{ $groupe->nom }} » ? Les électeurs ne sont pas supprimés." class="btn-danger min-h-10 px-4">Supprimer</button>
                                @endif
                            </div>
                        </div>
                        @if ($ouvert === $groupe->id)
                            <form wire:submit="enregistrerMembres" class="border-t border-trait p-4">
                                <label for="matricules-{{ $groupe->id }}" class="libelle">Matricules des membres</label>
                                <textarea id="matricules-{{ $groupe->id }}" wire:model="matricules" rows="6" class="champ chiffres" placeholder="20230001&#10;20230002"></textarea>
                                <p class="mt-1 text-xs text-brume">Un matricule par ligne, ou collez une colonne depuis un tableur. La liste remplace les membres actuels.</p>
                                @if ($bilan)
                                    <p class="mt-3 text-sm font-semibold text-bleu-700" role="status">{{ $bilan['membres'] }} {{ $bilan['membres'] > 1 ? 'membres enregistrés' : 'membre enregistré' }}.</p>
                                    @if ($bilan['inconnus'])
                                        <p class="erreur" role="alert">Absents de la liste électorale : {{ implode(', ', $bilan['inconnus']) }}</p>
                                    @endif
                                @endif
                                <button type="submit" class="btn-principal mt-3" wire:loading.attr="disabled">Enregistrer les membres</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="carte p-6 text-ardoise">Aucun groupe pour l’instant. Créez-en un pour un scrutin d’association ou de club.</li>
                @endforelse
            </ul>
        </section>

        <form wire:submit="creer" class="carte h-fit space-y-4 p-6">
            <h2 class="text-lg font-bold">Nouveau groupe</h2>
            <div>
                <label for="nom-groupe" class="libelle">Nom</label>
                <input id="nom-groupe" wire:model="nom" class="champ" placeholder="Club informatique" required>
                @error('nom') <p class="erreur">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description-groupe" class="libelle">Description <span class="font-normal text-brume">(facultatif)</span></label>
                <input id="description-groupe" wire:model="description" class="champ" placeholder="Membres à jour de cotisation">
                @error('description') <p class="erreur">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-sombre w-full">Créer le groupe</button>
        </form>
    </div>
</div>
