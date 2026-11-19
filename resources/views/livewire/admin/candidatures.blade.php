<div>
    <h1 class="text-3xl font-bold">Candidatures</h1>
    <p class="mt-1 text-ardoise">Une candidature validée est présentée aux électeurs ; un refus est expliqué au candidat, qui peut la corriger.</p>

    <div class="mt-6 flex flex-wrap gap-2" role="tablist">
        @foreach (['en_attente' => 'En attente', 'validee' => 'Validées', 'rejetee' => 'Refusées', 'toutes' => 'Toutes'] as $valeur => $libelle)
            <button type="button" wire:click="$set('filtre', '{{ $valeur }}')" role="tab" aria-selected="{{ $filtre === $valeur ? 'true' : 'false' }}"
                @class(['rounded-full px-4 py-2 text-sm font-medium transition', 'bg-nuit text-white' => $filtre === $valeur, 'bg-white text-ardoise ring-1 ring-trait hover:text-encre' => $filtre !== $valeur])>
                {{ $libelle }}
                @if ($valeur !== 'toutes')<span class="chiffres ml-1">{{ $compteurs[$valeur] ?? 0 }}</span>@endif
            </button>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @forelse ($candidatures as $c)
            <article class="carte flex flex-col p-5" wire:key="c-{{ $c->id }}">
                <div class="flex items-start gap-4">
                    <x-avatar :candidature="$c" taille="h-16 w-16 text-lg" />
                    <div class="min-w-0 flex-1">
                        <p class="text-lg font-bold">{{ $c->user->nomComplet() }}</p>
                        <p class="text-sm text-ardoise"><span class="chiffres">{{ $c->user->matricule }}</span> · {{ $c->user->classe?->nom }}</p>
                        <p class="mt-1 text-xs text-brume">{{ $c->scrutin->titre }}</p>
                    </div>
                    <span @class(['shrink-0', 'pastille-bleu' => $c->statut === 'validee', 'pastille-neutre' => $c->statut === 'en_attente', 'pastille-alerte' => $c->statut === 'rejetee'])>{{ $c->libelleStatut() }}</span>
                </div>
                <p class="mt-4 font-titre font-semibold text-bleu-900">« {{ $c->slogan }} »</p>
                <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-ardoise">{{ $c->programme }}</p>
                @if ($c->motif_rejet)<p class="mt-2 text-sm text-alerte">Motif : {{ $c->motif_rejet }}</p>@endif

                @if ($c->scrutin->ouverture_at->isFuture())
                    @if ($refusEnCours === $c->id)
                        <form wire:submit="refuser" class="mt-4 space-y-2 border-t border-trait pt-4">
                            <label for="motif-{{ $c->id }}" class="libelle">Motif du refus (envoyé au candidat)</label>
                            <input id="motif-{{ $c->id }}" wire:model="motif" class="champ" placeholder="Photo manquante, programme incomplet…" autofocus>
                            @error('motif') <p class="erreur">{{ $message }}</p> @enderror
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="$set('refusEnCours', null)" class="btn-contour min-h-9 px-3">Annuler</button>
                                <button type="submit" class="btn-danger min-h-9 px-3">Refuser</button>
                            </div>
                        </form>
                    @elseif ($c->statut === 'en_attente')
                        <div class="mt-auto flex justify-end gap-2 border-t border-trait pt-4">
                            <button type="button" wire:click="demanderRefus({{ $c->id }})" class="btn-danger min-h-9 px-3">Refuser</button>
                            <button type="button" wire:click="valider({{ $c->id }})" class="btn-principal min-h-9 px-4">Valider</button>
                        </div>
                    @endif
                @endif
            </article>
        @empty
            <p class="carte p-10 text-center text-ardoise lg:col-span-2">Aucune candidature dans cette catégorie.</p>
        @endforelse
    </div>
</div>
