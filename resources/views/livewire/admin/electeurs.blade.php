<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold">Électeurs</h1>
            <p class="mt-1 text-ardoise">La liste électorale. Un électeur désactivé ne peut ni se connecter ni voter.</p>
        </div>
        <a href="{{ route('admin.electeurs.csv', array_filter(['classe' => $classe])) }}" class="btn-contour"><x-icone nom="telecharger" class="h-4 w-4" /> Exporter (CSV)</a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <section>
            <div class="flex flex-col gap-3 sm:flex-row">
                <label class="relative flex-1">
                    <span class="sr-only">Rechercher</span>
                    <x-icone nom="recherche" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-brume" />
                    <input wire:model.live.debounce.300ms="recherche" class="champ pl-10" placeholder="Nom, matricule ou e-mail">
                </label>
                <select wire:model.live="classe" class="champ sm:w-44" aria-label="Classe">
                    <option value="">Toutes les classes</option>
                    @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                </select>
            </div>

            {{-- téléphone : une carte par électeur --}}
            <ul class="mt-4 space-y-2 sm:hidden">
                @forelse ($electeurs as $e)
                    <li wire:key="m-{{ $e->id }}" @class(['carte flex items-center gap-3 p-3', 'opacity-55' => ! $e->actif])>
                        <x-avatar :user="$e" taille="h-10 w-10 text-xs" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $e->nomComplet() }}</p>
                            <p class="chiffres truncate text-xs text-brume">{{ $e->matricule }} · {{ $e->classe?->nom }}</p>
                        </div>
                        <button type="button" wire:click="basculer({{ $e->id }})" role="switch" aria-checked="{{ $e->actif ? 'true' : 'false' }}"
                            @class(['pastille', 'bg-bleu-50 text-bleu-700 ring-1 ring-bleu-100' => $e->actif, 'bg-fond text-ardoise ring-1 ring-trait' => ! $e->actif])>
                            {{ $e->actif ? 'Actif' : 'Inactif' }}
                        </button>
                    </li>
                @empty
                    <li class="carte p-8 text-center text-ardoise">Aucun électeur trouvé.</li>
                @endforelse
            </ul>

            <div class="carte mt-4 hidden overflow-x-auto sm:block">
                <table class="w-full min-w-[620px] text-left text-sm">
                    <thead class="border-b border-trait text-xs text-brume">
                        <tr>
                            <th class="px-4 py-3 font-medium">Électeur</th>
                            <th class="px-4 py-3 font-medium">Matricule</th>
                            <th class="px-4 py-3 font-medium">Classe</th>
                            <th class="px-4 py-3 text-right font-medium">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-trait">
                        @forelse ($electeurs as $e)
                            <tr wire:key="e-{{ $e->id }}" @class(['opacity-55' => ! $e->actif])>
                                <td class="px-4 py-3"><p class="font-medium">{{ $e->nomComplet() }}</p><p class="text-xs text-brume">{{ $e->email }}</p></td>
                                <td class="chiffres px-4 py-3">{{ $e->matricule }}</td>
                                <td class="px-4 py-3">{{ $e->classe?->nom }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="basculer({{ $e->id }})" role="switch" aria-checked="{{ $e->actif ? 'true' : 'false' }}"
                                        @class(['pastille', 'bg-bleu-50 text-bleu-700 ring-1 ring-bleu-100' => $e->actif, 'bg-fond text-ardoise ring-1 ring-trait' => ! $e->actif])>
                                        {{ $e->actif ? 'Actif' : 'Inactif' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-ardoise">Aucun électeur trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $electeurs->links() }}</div>
        </section>

        <aside class="carte h-fit space-y-4 p-5">
            <h2 class="flex items-center gap-2 text-lg font-bold"><x-icone nom="televerser" class="text-bleu-600" /> Importer une liste</h2>
            <p class="text-sm text-ardoise">Fichier CSV exporté par la scolarité. Un matricule déjà connu est mis à jour.</p>
            <pre class="whitespace-pre-wrap break-all rounded-md bg-nuit p-3 font-mono text-[11px] leading-relaxed text-bleu-100">Matricule,Nom,Prénom,Email,Statut,Classe
20230001,RABE,Jean,jean.rabe@univ-mg.edu,actif,L2</pre>
            <form wire:submit="importer" class="space-y-3">
                <input type="file" wire:model="fichier" accept=".csv,text/csv" aria-label="Fichier CSV"
                    class="block w-full text-sm text-ardoise file:mr-3 file:rounded-lg file:border-0 file:bg-bleu-50 file:px-4 file:py-2 file:font-semibold file:text-bleu-700">
                @error('fichier') <p class="erreur">{{ $message }}</p> @enderror
                <button type="submit" class="btn-sombre w-full" wire:loading.attr="disabled" @disabled(! $fichier)>Importer</button>
            </form>
            @if ($bilan)
                <div class="rounded-md bg-fond p-3 text-sm" role="status">
                    <p><span class="chiffres font-semibold">{{ $bilan['crees'] }}</span> créé(s), <span class="chiffres font-semibold">{{ $bilan['mis_a_jour'] }}</span> mis à jour.</p>
                    @foreach (array_slice($bilan['erreurs'], 0, 8) as $erreur)<p class="mt-1 text-alerte">{{ $erreur }}</p>@endforeach
                    @if (count($bilan['erreurs']) > 8)<p class="mt-1 text-ardoise">… et {{ count($bilan['erreurs']) - 8 }} autre(s).</p>@endif
                </div>
            @endif
        </aside>
    </div>
</div>
