@props(['scrutin', 'sombre' => false])
@php $etat = $scrutin->etat(); @endphp
<span @class([
    'pastille bg-white/10 text-white ring-1 ring-white/20' => $sombre,
    'pastille-nuit' => ! $sombre && $etat === \App\Enums\EtatScrutin::Ouvert,
    'pastille-bleu' => ! $sombre && in_array($etat, [\App\Enums\EtatScrutin::Programme, \App\Enums\EtatScrutin::Publie]),
    'pastille-neutre' => ! $sombre && in_array($etat, [\App\Enums\EtatScrutin::Brouillon, \App\Enums\EtatScrutin::Clos]),
])>
    @if ($etat === \App\Enums\EtatScrutin::Ouvert)<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-bleu-300"></span>@endif
    {{ $etat->libelle() }}
</span>
