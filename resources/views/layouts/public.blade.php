<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-tete :titre="$title ?? null" />
</head>
<body class="min-h-screen bg-papier">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-papier focus:px-4 focus:py-2">{{ __('Aller au contenu') }}</a>
    <header class="sticky top-0 z-40 border-b border-trait bg-papier/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <a href="{{ route('accueil') }}" wire:navigate class="shrink-0" aria-label="{{ __('Accueil') }}"><x-logo etroit /></a>
            <nav class="flex items-center gap-1 text-[15px] font-semibold sm:gap-3" aria-label="{{ __('Navigation principale') }}">
                @foreach ([['resultats.publics', __('Résultats')], ['verifier', __('Vérifier un reçu')]] as [$route, $libelle])
                    <a href="{{ route($route) }}" wire:navigate @class([
                        'hidden px-2 py-1 underline-offset-[6px] hover:underline md:inline-flex decoration-2 decoration-bleu-700',
                        'underline' => request()->routeIs($route),
                    ])>{{ $libelle }}</a>
                @endforeach
                <x-choix-langue />
                @auth
                    <a href="{{ auth()->user()->estAdmin() ? route('admin.tableau') : route('espace') }}" wire:navigate class="btn-sombre min-h-10 whitespace-nowrap px-4">{{ __('Mon espace') }}</a>
                @else
                    <a href="{{ route('connexion') }}" wire:navigate class="btn-sombre min-h-10 whitespace-nowrap px-4">{{ __('Se connecter') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenu">{{ $slot }}</main>

    <footer class="fond-nuit text-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-12 sm:flex-row sm:justify-between sm:px-6">
            <div class="max-w-sm">
                <x-logo clair />
                <p class="mt-4 text-sm text-white/65">{{ __('Un électeur, un vote. Secret, vérifiable, dépouillé automatiquement.') }}</p>
            </div>
            <ul class="grid grid-cols-2 gap-x-10 gap-y-2.5 text-sm text-white/80 sm:text-right">
                <li><a href="{{ route('connexion') }}" wire:navigate class="hover:text-white hover:underline">{{ __('Se connecter') }}</a></li>
                <li><a href="{{ route('resultats.publics') }}" wire:navigate class="hover:text-white hover:underline">{{ __('Résultats publiés') }}</a></li>
                <li><a href="{{ route('verifier') }}" wire:navigate class="hover:text-white hover:underline">{{ __('Vérifier mon reçu') }}</a></li>
                <li><a href="{{ route('confidentialite') }}" wire:navigate class="hover:text-white hover:underline">{{ __('Politique de confidentialité') }}</a></li>
                <li><a href="{{ route('aide') }}" wire:navigate class="hover:text-white hover:underline">{{ __('Guide d’utilisation') }}</a></li>
            </ul>
        </div>
        <p class="border-t border-white/15 px-4 py-5 text-center text-xs text-white/50">© {{ date('Y') }} {{ __('Vote électronique') }} · {{ __('Projet académique de JERA Bevala Juliot, ESMIA Innovation') }}</p>
    </footer>
    {{-- Alpine (bulle, menu, comptes à rebours) aussi sur les pages qui ne sont pas des composants Livewire --}}
    @livewireScripts
</body>
</html>
