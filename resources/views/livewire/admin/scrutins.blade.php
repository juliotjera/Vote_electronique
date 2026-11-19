<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Scrutins</h1>
            <p class="mt-1 text-ardoise">Du brouillon à la publication des résultats.</p>
        </div>
        <a href="{{ route('admin.scrutins.nouveau') }}" wire:navigate class="btn-principal"><x-icone nom="plus" class="h-4 w-4" /> Nouveau scrutin</a>
    </div>

    {{-- téléphone : une carte par scrutin --}}
    <ul class="mt-6 space-y-3 md:hidden">
        @forelse ($scrutins as $scrutin)
            <li>
                <a href="{{ route('admin.scrutins.suivi', $scrutin) }}" wire:navigate class="carte block p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold">{{ $scrutin->titre }}</p>
                        <x-etat-scrutin :scrutin="$scrutin" />
                    </div>
                    <p class="chiffres mt-1 text-sm text-ardoise">{{ $scrutin->publicCible() ?: '—' }} · {{ $scrutin->ouverture_at->translatedFormat('j M') }} → {{ $scrutin->cloture_at->translatedFormat('j M Y') }}</p>
                    <p class="chiffres mt-1 text-sm">{{ $scrutin->candidatures_count }} candidat(s)@if ($scrutin->programme_at && $scrutin->ouverture_at->isPast()) · participation {{ number_format($scrutin->tauxParticipation(), 1, ',', ' ') }} %@endif</p>
                </a>
            </li>
        @empty
            <li class="carte p-8 text-center text-ardoise">Aucun scrutin. Créez le premier.</li>
        @endforelse
    </ul>

    <div class="carte mt-6 hidden overflow-x-auto md:block">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-trait text-xs text-brume">
                <tr>
                    <th class="px-5 py-3 font-medium">Scrutin</th>
                    <th class="px-5 py-3 font-medium">Classes</th>
                    <th class="px-5 py-3 font-medium">Période</th>
                    <th class="px-5 py-3 font-medium">Candidats</th>
                    <th class="px-5 py-3 font-medium">Participation</th>
                    <th class="px-5 py-3 font-medium">État</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-trait">
                @forelse ($scrutins as $scrutin)
                    <tr class="hover:bg-fond">
                        <td class="px-5 py-4"><a href="{{ route('admin.scrutins.suivi', $scrutin) }}" wire:navigate class="font-semibold hover:text-bleu-700">{{ $scrutin->titre }}</a></td>
                        <td class="px-5 py-4 text-ardoise">{{ $scrutin->publicCible() ?: '—' }}</td>
                        <td class="chiffres px-5 py-4 text-ardoise">{{ $scrutin->ouverture_at->translatedFormat('j M') }} → {{ $scrutin->cloture_at->translatedFormat('j M Y') }}</td>
                        <td class="chiffres px-5 py-4">{{ $scrutin->candidatures_count }}</td>
                        <td class="chiffres px-5 py-4">{{ $scrutin->programme_at && $scrutin->ouverture_at->isPast() ? number_format($scrutin->tauxParticipation(), 1, ',', ' ').' %' : '—' }}</td>
                        <td class="px-5 py-4"><x-etat-scrutin :scrutin="$scrutin" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-ardoise">Aucun scrutin. Créez le premier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
