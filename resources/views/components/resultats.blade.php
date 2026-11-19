@props(['scrutin', 'r'])
{{-- Résultats d'un scrutin : les élus d'abord, la participation en anneau, puis le classement complet --}}
@php
    $elus = $r['candidats']->where('elu', true)->values();
    $exAequo = $r['candidats']->where('exAequo', true)->values();
    $circonference = 2 * M_PI * 52;
@endphp
<div class="space-y-6">
    <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        {{-- Les élus, ou l'égalité à départager --}}
        <section class="fond-nuit relative overflow-hidden rounded-lg p-6 text-white shadow-feuille sm:p-8" aria-labelledby="elus-titre">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-bleu-500/20 blur-3xl" aria-hidden="true"></div>
            <p id="elus-titre" class="relative flex items-center gap-2 text-sm font-semibold text-bleu-300">
                <x-icone nom="medaille" class="h-4 w-4" />
                @if ($r['egalite']) {{ __('Égalité pour le dernier siège') }}
                @else {{ trans_choice('Élu|Élus', max(1, $elus->count())) }} @endif
            </p>
            <ul class="relative mt-5 space-y-5">
                @forelse ($r['egalite'] ? $exAequo : $elus as $c)
                    <li class="flex items-center gap-4">
                        <x-avatar :candidature="$c" taille="h-16 w-16 text-xl" class="ring-2 ring-white/20" />
                        <div class="min-w-0">
                            <p class="truncate font-titre text-2xl font-bold sm:text-3xl">{{ $c->user->nomComplet() }}</p>
                            <p class="chiffres text-white/70">{{ trans_choice(':n voix|:n voix', $c->voix, ['n' => $c->voix]) }} · {{ number_format($c->pourcentage, 1, ',', ' ') }} %</p>
                        </div>
                    </li>
                @empty
                    <li class="text-white/70">{{ __('Aucun élu : aucune voix exprimée pour un candidat.') }}</li>
                @endforelse
            </ul>
            @if ($r['egalite'])
                <p class="relative mt-5 text-sm text-white/65">{{ __('Les candidats ex aequo sont départagés selon le règlement de l’établissement (second tour ou tirage).') }}</p>
            @endif
        </section>

        {{-- Participation --}}
        <section class="carte flex items-center gap-6 p-6" aria-label="{{ __('Participation') }}">
            <div class="relative h-32 w-32 shrink-0">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" aria-hidden="true">
                    <defs>
                        <linearGradient id="anneau-{{ $scrutin->id }}" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#1442a8" /><stop offset="1" stop-color="#3b7bf6" />
                        </linearGradient>
                    </defs>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e3e8f0" stroke-width="12" />
                    <circle cx="60" cy="60" r="52" fill="none" stroke="url(#anneau-{{ $scrutin->id }})" stroke-width="12" stroke-linecap="round"
                        stroke-dasharray="{{ round($circonference * min(100, $r['participation']) / 100, 2) }} {{ round($circonference, 2) }}" />
                </svg>
                <p class="chiffres absolute inset-0 flex flex-col items-center justify-center font-titre text-2xl font-bold">
                    {{ number_format($r['participation'], 1, ',', ' ') }}<span class="text-xs font-medium text-brume">%</span>
                </p>
            </div>
            <dl class="chiffres grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div class="col-span-2"><dt class="text-brume">{{ __('Votants') }}</dt><dd class="font-titre text-lg font-bold">{{ $r['votants'] }} <span class="text-sm font-medium text-ardoise">/ {{ $r['inscrits'] }} {{ __('inscrits') }}</span></dd></div>
                <div><dt class="text-brume">{{ __('Exprimés') }}</dt><dd class="font-semibold">{{ $r['exprimes'] }}</dd></div>
                <div><dt class="text-brume">{{ __('Blancs') }}</dt><dd class="font-semibold">{{ $r['blancs'] }}</dd></div>
            </dl>
        </section>
    </div>

    {{-- Classement complet --}}
    <ol class="carte divide-y divide-trait" aria-label="{{ __('Classement') }}">
        @foreach ($r['candidats'] as $i => $candidat)
            <li class="flex items-center gap-4 p-4 sm:p-5">
                <span class="chiffres w-6 shrink-0 text-center font-titre text-lg font-bold text-brume">{{ $i + 1 }}</span>
                <x-avatar :candidature="$candidat" taille="h-12 w-12 text-sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold">{{ $candidat->user->nomComplet() }}</p>
                        @if ($candidat->elu)<span class="pastille-nuit"><x-icone nom="medaille" class="h-3.5 w-3.5" /> {{ __('Élu') }}</span>@endif
                        @if ($candidat->exAequo)<span class="pastille-bleu">{{ __('Ex aequo') }}</span>@endif
                    </div>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-fond">
                            <div @class(['h-full rounded-full', 'fond-tampon' => $candidat->elu || $candidat->exAequo, 'bg-bleu-300' => ! $candidat->elu && ! $candidat->exAequo])
                                style="width: {{ $candidat->pourcentage }}%"></div>
                        </div>
                        <span class="chiffres w-28 shrink-0 text-right text-sm"><span class="font-semibold">{{ $candidat->voix }}</span> {{ __('voix') }} · {{ number_format($candidat->pourcentage, 1, ',', ' ') }} %</span>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
    <p class="text-xs text-brume">{{ __('Pourcentages calculés sur les bulletins exprimés (hors votes blancs).') }}@if ($scrutin->nombre_choix > 1) {{ __('Un bulletin pouvant porter plusieurs noms, leur somme dépasse 100 %.') }}@endif</p>
</div>
