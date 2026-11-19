<div wire:poll.30s>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Tableau de bord</h1>
            <p class="mt-1 text-ardoise">{{ now()->translatedFormat('l j F Y') }}</p>
        </div>
        <a href="{{ route('admin.scrutins.nouveau') }}" wire:navigate class="btn-principal"><x-icone nom="plus" class="h-4 w-4" /> Nouveau scrutin</a>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Électeurs inscrits', $electeurs, 'utilisateurs', route('admin.electeurs')],
            ['Votes en cours', $enCours->count(), 'urne', route('admin.scrutins')],
            ['Candidatures à examiner', $candidaturesEnAttente, 'utilisateur', route('admin.candidatures')],
            ['Résultats publiés', $publies, 'graphique', route('admin.scrutins')],
        ] as [$libelle, $valeur, $icone, $lien])
            <a href="{{ $lien }}" wire:navigate class="carte p-5 transition hover:border-bleu-300">
                <span class="flex items-center gap-2 text-sm text-ardoise"><x-icone :nom="$icone" class="h-4 w-4 text-bleu-600" /> {{ $libelle }}</span>
                <span class="chiffres mt-2 block font-titre text-3xl font-bold">{{ $valeur }}</span>
            </a>
        @endforeach
    </div>

    {{-- Votes en cours : la participation se suit en direct, jamais les voix --}}
    @foreach ($enCours as $scrutin)
        <a href="{{ route('admin.scrutins.suivi', $scrutin) }}" wire:navigate class="fond-admin mt-6 block rounded-lg p-6 text-white shadow-feuille sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-etat-scrutin :scrutin="$scrutin" sombre />
                <span class="text-sm text-white/65">Clôture {{ $scrutin->cloture_at->diffForHumans() }}</span>
            </div>
            <h2 class="mt-3 text-2xl font-bold">{{ $scrutin->titre }}</h2>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="chiffres font-titre text-5xl font-bold">{{ number_format($scrutin->tauxParticipation(), 1, ',', ' ') }} %</p>
                <p class="chiffres text-right text-sm text-white/70">{{ $scrutin->nombreVotants() }} votants<br>sur {{ $scrutin->nombreInscrits() }} inscrits</p>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full bg-bleu-300" style="width: {{ min(100, $scrutin->tauxParticipation()) }}%"></div>
            </div>
            <p class="mt-3 text-xs text-white/55">Les voix ne sont pas visibles avant la clôture, pour ne pas influencer les électeurs.</p>
        </a>
    @endforeach

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
        <section class="carte p-6">
            <h2 class="text-lg font-bold">À faire</h2>
            <ul class="mt-4 divide-y divide-trait">
                @foreach ($aPublier as $scrutin)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div><p class="font-medium">{{ $scrutin->titre }}</p><p class="text-sm text-ardoise">Vote clos : vérifier le dépouillement et publier</p></div>
                        <a href="{{ route('admin.scrutins.suivi', $scrutin) }}" wire:navigate class="btn-sombre min-h-9 shrink-0 px-3">Publier</a>
                    </li>
                @endforeach
                @if ($candidaturesEnAttente)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div><p class="font-medium">{{ $candidaturesEnAttente }} candidature(s) en attente</p><p class="text-sm text-ardoise">À valider avant l’ouverture des votes</p></div>
                        <a href="{{ route('admin.candidatures') }}" wire:navigate class="btn-contour min-h-9 shrink-0 px-3">Examiner</a>
                    </li>
                @endif
                @foreach ($brouillons as $scrutin)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div><p class="font-medium">{{ $scrutin->titre }}</p><p class="text-sm text-ardoise">Brouillon : à programmer pour l’annoncer aux électeurs</p></div>
                        <a href="{{ route('admin.scrutins.suivi', $scrutin) }}" wire:navigate class="btn-contour min-h-9 shrink-0 px-3">Ouvrir</a>
                    </li>
                @endforeach
                @foreach ($aVenir as $scrutin)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div><p class="font-medium">{{ $scrutin->titre }}</p><p class="text-sm text-ardoise">Ouverture {{ $scrutin->ouverture_at->diffForHumans() }}</p></div>
                        <x-etat-scrutin :scrutin="$scrutin" />
                    </li>
                @endforeach
                @if ($aPublier->isEmpty() && ! $candidaturesEnAttente && $brouillons->isEmpty() && $aVenir->isEmpty())
                    <li class="py-6 text-center text-sm text-ardoise">Rien en attente.</li>
                @endif
            </ul>
        </section>

        <section class="carte p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Activité récente</h2>
                <a href="{{ route('admin.journal') }}" wire:navigate class="text-sm font-semibold text-bleu-700 hover:underline">Tout le journal</a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($activite as $ligne)
                    <li class="flex items-start justify-between gap-3">
                        <span><span class="font-medium">{{ $ligne->user?->nomComplet() ?? 'Système' }}</span> · {{ \App\Livewire\Admin\JournalActivite::libelle($ligne->action) }}</span>
                        <time class="shrink-0 text-xs text-brume">{{ $ligne->created_at->diffForHumans(short: true) }}</time>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
