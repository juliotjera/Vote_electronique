@props(['clair' => false])
{{-- Français / Malagasy : un lien par langue, la langue active est marquée --}}
<div {{ $attributes->merge(['class' => 'inline-flex rounded-lg p-0.5 text-xs font-semibold '.($clair ? 'bg-white/10' : 'bg-fond ring-1 ring-trait')]) }} role="group" aria-label="{{ __('Langue') }}">
    @foreach (\App\Http\Middleware\DefinirLangue::LANGUES as $code => $nom)
        <a href="{{ route('langue', $code) }}" lang="{{ $code }}" title="{{ $nom }}" @if (app()->getLocale() === $code) aria-current="true" @endif
            @class([
                'rounded-md px-2 py-1 uppercase transition',
                'bg-white text-encre shadow-sm' => app()->getLocale() === $code && ! $clair,
                'bg-white text-bleu-900' => app()->getLocale() === $code && $clair,
                'text-ardoise hover:text-encre' => app()->getLocale() !== $code && ! $clair,
                'text-white/70 hover:text-white' => app()->getLocale() !== $code && $clair,
            ])>{{ $code }}</a>
    @endforeach
</div>
