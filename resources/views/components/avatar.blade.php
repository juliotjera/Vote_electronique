@props(['candidature' => null, 'user' => null, 'taille' => 'h-14 w-14 text-base'])
@php
    $personne = $candidature?->user ?? $user;
    // sans photo : un aplat de la palette propre à chaque personne (toujours le même), initiales lisibles
    $nuances = [['#dde3fa', '#0d1b5e'], ['#1b3bb3', '#ffffff'], ['#eff2fd', '#142a8a'], ['#000000', '#ffffff'], ['#a7b6ef', '#08113a']];
    [$fond, $texte] = $nuances[$personne->id % count($nuances)];
@endphp
@if ($candidature?->photo_id)
    <img src="{{ $candidature->urlPhoto() }}" alt="{{ __('Photo de :nom', ['nom' => $personne->nomComplet()]) }}" loading="lazy"
        {{ $attributes->merge(['class' => "$taille shrink-0 rounded-full object-cover"]) }}>
@else
    <span aria-hidden="true" style="background: {{ $fond }}; color: {{ $texte }}"
        {{ $attributes->merge(['class' => "$taille font-titre inline-flex shrink-0 items-center justify-center rounded-full font-semibold"]) }}>{{ $personne->initiales() }}</span>
@endif
