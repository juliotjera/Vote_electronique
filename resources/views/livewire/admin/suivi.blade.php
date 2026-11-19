<div @if ($etat === \App\Enums\EtatScrutin::Ouvert) wire:poll.15s @endif>
    <a href="{{ route('admin.scrutins') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
        <x-icone nom="retour" class="h-4 w-4" /> Scrutins
    </a>

    <header class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-etat-scrutin :scrutin="$scrutin" />
                <span class="pastille-neutre">{{ $scrutin->libelleType() }}</span>
                <span class="pastille-neutre">{{ $scrutin->publicCible() }}</span>
            </div>
            <h1 class="mt-3 text-3xl font-bold">{{ $scrutin->titre }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($etat === \App\Enums\EtatScrutin::Brouillon)
                <button type="button" wire:click="supprimer" wire:confirm="Supprimer ce brouillon ?" class="btn-danger">Supprimer</button>
                <a href="{{ route('admin.scrutins.modifier', $scrutin) }}" wire:navigate class="btn-contour">Modifier</a>
                <button type="button" wire:click="programmer" wire:confirm="Programmer ce scrutin ? Il sera annoncé aux électeurs et ne pourra plus être modifié." class="btn-principal">Programmer</button>
            @endif
            @if ($etat === \App\Enums\EtatScrutin::Clos)
                <button type="button" wire:click="publier" wire:confirm="Publier les résultats et avertir les électeurs ?" class="btn-principal">Publier les résultats</button>
            @endif
            @if (in_array($etat, [\App\Enums\EtatScrutin::Clos, \App\Enums\EtatScrutin::Publie]))
                <a href="{{ route('admin.scrutins.pdf', $scrutin) }}" class="btn-contour"><x-icone nom="telecharger" class="h-4 w-4" /> Procès-verbal PDF</a>
                <a href="{{ route('admin.scrutins.csv', $scrutin) }}" class="btn-contour"><x-icone nom="telecharger" class="h-4 w-4" /> CSV</a>
            @endif
        </div>
    </header>
    @error('scrutin') <p class="mt-4 rounded-md bg-alerte-50 px-4 py-3 text-sm text-alerte ring-1 ring-alerte/20">{{ $message }}</p> @enderror

    {{-- Calendrier : les étapes se suivent dans l'ordre --}}
    @php
        $etapes = [
            ['Candidatures', $scrutin->candidatures_fin_at ? 'jusqu’au '.$scrutin->candidatures_fin_at->translatedFormat('j M, H\hi') : 'jusqu’à l’ouverture'],
            ['Ouverture', $scrutin->ouverture_at->translatedFormat('j M Y, H\hi')],
            ['Clôture', $scrutin->cloture_at->translatedFormat('j M Y, H\hi')],
            ['Publication', $scrutin->publie_at?->translatedFormat('j M Y, H\hi') ?? 'après vérification'],
        ];
        $faites = match ($etat) {
            \App\Enums\EtatScrutin::Brouillon => 0, \App\Enums\EtatScrutin::Programme => 0,
            \App\Enums\EtatScrutin::Ouvert => 2, \App\Enums\EtatScrutin::Clos => 3, \App\Enums\EtatScrutin::Publie => 4,
        };
    @endphp
    <ol class="mt-6 grid gap-3 sm:grid-cols-4">
        @foreach ($etapes as $i => [$libelle, $date])
            <li @class(['rounded-lg p-4 ring-1', 'fond-tampon text-white ring-transparent' => $i < $faites, 'bg-white ring-trait' => $i >= $faites])>
                <p class="chiffres text-xs {{ $i < $faites ? 'text-white/70' : 'text-brume' }}">{{ $i + 1 }}. {{ $libelle }}</p>
                <p class="chiffres mt-1 text-sm font-semibold">{{ $date }}</p>
            </li>
        @endforeach
    </ol>

    @if ($etat === \App\Enums\EtatScrutin::Ouvert)
        <section class="fond-admin mt-6 rounded-lg p-6 text-white sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm text-white/65">Participation en direct</p>
                    <p class="chiffres font-titre text-5xl font-bold">{{ number_format($scrutin->tauxParticipation(), 1, ',', ' ') }} %</p>
                </div>
                <p class="chiffres text-right text-sm text-white/70">{{ $scrutin->nombreVotants() }} votants sur {{ $scrutin->nombreInscrits() }} inscrits<br>clôture {{ $scrutin->cloture_at->diffForHumans() }}</p>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-bleu-300" style="width: {{ min(100, $scrutin->tauxParticipation()) }}%"></div></div>
            <p class="mt-3 text-xs text-white/55">Les voix restent scellées jusqu’à la clôture : personne, administration comprise, ne les voit pendant le vote.</p>
        </section>
    @endif

    @if ($integrite)
        <div @class(['mt-6 flex items-start gap-3 rounded-lg p-4 text-sm ring-1',
            'bg-bleu-50 text-bleu-900 ring-bleu-100' => ! $integrite['probleme'],
            'bg-alerte-50 text-alerte ring-alerte/20' => $integrite['probleme']])>
            <x-icone :nom="$integrite['probleme'] ? 'info' : 'chaine'" class="mt-0.5 h-4 w-4" />
            <p>
                <span class="font-semibold">Intégrité de l’urne : {{ $integrite['probleme'] ? 'anomalie' : 'vérifiée' }}.</span>
                {{ $integrite['probleme'] ?? "Chaîne d'empreintes intacte sur {$integrite['bulletins']} bulletin(s), autant que de votants émargés." }}
            </p>
        </div>
    @endif

    @if ($r)
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-bold">Dépouillement</h2>
            <x-resultats :scrutin="$scrutin" :r="$r" />
        </section>
    @endif

    @if ($etat !== \App\Enums\EtatScrutin::Brouillon)
        <section class="mt-8" aria-labelledby="emargement-titre">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="emargement-titre" class="text-xl font-bold">Liste d’émargement</h2>
                    <p class="mt-1 text-sm text-ardoise">Qui a voté et quel jour, jamais pour qui. Triée par nom, jamais dans l’ordre des votes ; supprimée automatiquement 30 jours après la clôture.</p>
                </div>
                @if (! $scrutin->purge_at && $emargements->isNotEmpty())
                    <a href="{{ route('admin.scrutins.emargement', $scrutin) }}" class="btn-contour"><x-icone nom="telecharger" class="h-4 w-4" /> Exporter (CSV)</a>
                @endif
            </div>
            @if ($scrutin->purge_at)
                <p class="carte mt-4 p-4 text-sm text-ardoise">Liste supprimée le {{ $scrutin->purge_at->translatedFormat('j F Y') }}. Les chiffres de participation ont été figés avant la suppression.</p>
            @elseif ($emargements->isEmpty())
                <p class="carte mt-4 p-4 text-sm text-ardoise">Personne n’a encore voté.</p>
            @else
                <details class="carte mt-4">
                    <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-3 px-4 font-semibold">
                        <span>{{ $emargements->count() }} {{ $emargements->count() > 1 ? 'électeurs ont émargé' : 'électeur a émargé' }}</span>
                        <span class="text-sm font-medium text-bleu-700">Afficher la liste</span>
                    </summary>
                    <div class="overflow-x-auto border-t border-trait">
                        <table class="w-full min-w-[520px] text-left text-sm">
                            <thead class="text-xs text-brume"><tr><th class="px-4 py-2 font-medium">Matricule</th><th class="px-4 py-2 font-medium">Nom</th><th class="px-4 py-2 font-medium">Classe</th><th class="px-4 py-2 font-medium">Date du vote</th></tr></thead>
                            <tbody class="divide-y divide-trait">
                                @foreach ($emargements as $e)
                                    <tr><td class="chiffres px-4 py-2 text-ardoise">{{ $e->matricule }}</td><td class="px-4 py-2 font-medium">{{ $e->nom }} {{ $e->prenom }}</td><td class="px-4 py-2">{{ $e->classe }}</td><td class="chiffres px-4 py-2 text-ardoise">{{ $e->date_vote?->translatedFormat('j M Y') }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </section>
    @endif

    <section class="mt-8">
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-xl font-bold">Candidatures</h2>
            <a href="{{ route('admin.candidatures') }}" wire:navigate class="text-sm font-semibold text-bleu-700 hover:underline">Examiner les candidatures</a>
        </div>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($candidatures as $c)
                <li class="carte flex items-center gap-3 p-4">
                    <x-avatar :candidature="$c" taille="h-11 w-11 text-sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $c->user->nomComplet() }}</p>
                        <p class="truncate text-sm text-ardoise">« {{ $c->slogan }} »</p>
                    </div>
                    <span @class(['pastille-bleu' => $c->statut === 'validee', 'pastille-neutre' => $c->statut === 'en_attente', 'pastille-alerte' => $c->statut === 'rejetee'])>{{ $c->libelleStatut() }}</span>
                </li>
            @empty
                <li class="carte p-6 text-sm text-ardoise sm:col-span-2">
                    {{ $etat === \App\Enums\EtatScrutin::Brouillon ? 'Les électeurs pourront se porter candidats une fois le scrutin programmé.' : 'Aucune candidature pour l’instant.' }}
                </li>
            @endforelse
        </ul>
    </section>
</div>
