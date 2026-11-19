<div>
    <h1 class="text-3xl font-bold">Journal d’activité</h1>
    <p class="mt-1 text-ardoise">Qui a fait quoi, et quand, y compris les tentatives refusées. Les votes n’y figurent pas : leur heure, rapprochée de l’ordre des bulletins, pourrait trahir un choix. Les adresses IP sont tronquées.</p>

    <div class="mt-6 flex flex-wrap items-center gap-2" role="group" aria-label="Filtrer le journal">
        @foreach (['tout' => 'Toutes les actions', 'refus' => 'Tentatives refusées'] as $valeur => $libelle)
            <button type="button" wire:click="$set('filtre', '{{ $valeur }}')" aria-pressed="{{ $filtre === $valeur ? 'true' : 'false' }}"
                @class(['min-h-10 rounded-full px-4 text-sm font-semibold transition', 'bg-nuit text-white' => $filtre === $valeur, 'bg-papier text-ardoise ring-1 ring-trait hover:text-encre' => $filtre !== $valeur])>{{ $libelle }}</button>
        @endforeach
        @if ($refus)<span class="pastille-alerte ml-auto">{{ $refus }} refus sur 24 h</span>@endif
    </div>

    <div class="carte mt-4 overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-trait text-xs text-brume">
                <tr>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium">Personne</th>
                    <th class="px-4 py-3 font-medium">Action</th>
                    <th class="px-4 py-3 font-medium">Adresse IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-trait">
                @forelse ($lignes as $ligne)
                    <tr>
                        <td class="chiffres whitespace-nowrap px-4 py-3 text-ardoise">{{ $ligne->created_at->translatedFormat('j M Y, H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $ligne->user?->nomComplet() ?? (in_array($ligne->action, \App\Livewire\Admin\JournalActivite::REFUS, true) ? 'Inconnu' : 'Système') }}</td>
                        <td class="px-4 py-3">
                            <span @class(['font-semibold text-alerte' => in_array($ligne->action, \App\Livewire\Admin\JournalActivite::REFUS, true)])>{{ \App\Livewire\Admin\JournalActivite::libelle($ligne->action) }}</span>@if ($ligne->details['demo'] ?? false) <span class="pastille-neutre ml-1">démo</span>@endif
                            @if ($motif = $ligne->details['motif'] ?? null)<span class="block text-xs text-brume">{{ \App\Livewire\Admin\JournalActivite::MOTIFS[$motif] ?? $motif }}</span>@endif
                        </td>
                        <td class="chiffres px-4 py-3 text-brume">{{ $ligne->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-ardoise">Le journal est vide.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $lignes->links() }}</div>
</div>
