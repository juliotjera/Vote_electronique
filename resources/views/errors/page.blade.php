{{-- Page d'erreur à la charte. Volontairement autonome : aucune requête en base, aucun composant Livewire,
     pour s'afficher même quand la base ou la session ne répondent plus. --}}
@php
    $message = isset($exception) && $exception->getMessage() && in_array($code, [403, 409, 410], true) ? $exception->getMessage() : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-tete :titre="$titre" />
</head>
<body class="flex min-h-screen flex-col bg-fond">
    <header class="border-b border-trait bg-papier">
        <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6">
            <a href="{{ url('/') }}" aria-label="{{ __('Accueil') }}"><x-logo etroit /></a>
        </div>
    </header>

    <main id="contenu" class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-4 py-16 sm:px-6">
        <p class="chiffres font-titre text-6xl font-bold text-bleu-700">{{ $code }}</p>
        <h1 class="mt-3 text-3xl sm:text-4xl">{{ $titre }}</h1>
        <p class="mt-3 text-lg leading-relaxed text-ardoise">{{ $texte }}</p>
        @if ($message)
            <p class="carte mt-5 p-4 text-ardoise">{{ $message }}</p>
        @endif
        <div class="mt-8 flex flex-wrap gap-3">
            @if ($code === 419)
                <a href="{{ url()->current() }}" class="btn-principal">{{ __('Recharger la page') }}</a>
            @else
                <a href="{{ url('/') }}" class="btn-principal">{{ __('Retour à l’accueil') }}</a>
            @endif
            @if (in_array($code, [403, 404, 410], true))
                <a href="{{ url('/espace') }}" class="btn-contour">{{ __('Mon espace') }}</a>
            @endif
        </div>
    </main>

    <footer class="fond-nuit px-4 py-5 text-center text-xs text-white/60">© {{ date('Y') }} {{ __('Vote électronique') }}</footer>
</body>
</html>
