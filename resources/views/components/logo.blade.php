@props(['clair' => false, 'compact' => false, 'etroit' => false])
{{-- Une tuile bleue en dégradé : un bulletin coché glisse dans la fente d’une urne, lisible jusqu’en favicon. --}}
@php
    // dégradé bleu foncé → bleu clair (cahier des charges, 8.1) ; un identifiant par logo affiché
    $id = 'logo-'.\Illuminate\Support\Str::random(6);
    $tuile = $clair ? '#ffffff' : "url(#$id)";
    $signe = $clair ? '#1d4ed8' : '#ffffff';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 36 36" class="h-9 w-9 shrink-0" aria-hidden="true">
        <defs><linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1e40af" /><stop offset="1" stop-color="#3b82f6" /></linearGradient></defs>
        <rect width="36" height="36" rx="9" fill="{{ $tuile }}" />
        <rect x="11.5" y="6.5" width="13" height="13.5" rx="2" fill="{{ $signe }}" />
        <path d="M14.6 13.2l2.4 2.4 4.4-4.8" fill="none" stroke="#1d4ed8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M7.5 21.5h21v6a2.5 2.5 0 0 1-2.5 2.5H10a2.5 2.5 0 0 1-2.5-2.5z" fill="{{ $signe }}" />
    </svg>
    {{-- étroit : sur un petit téléphone, seul le symbole reste (le nom revient dès 420 px) --}}
    <span @class(['leading-none', 'hidden min-[420px]:block' => $etroit])>
        <span class="font-titre block whitespace-nowrap text-[17px] font-bold tracking-[-0.02em] {{ $clair ? 'text-white' : 'text-encre' }}">{{ __('Vote électronique') }}</span>
        @unless ($compact)<span class="mt-1 block text-[12px] {{ $clair ? 'text-white/65' : 'text-brume' }}">{{ __('Élections étudiantes') }}</span>@endunless
    </span>
</span>
