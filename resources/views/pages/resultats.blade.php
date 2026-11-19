<x-dynamic-component :component="auth()->check() ? 'layouts::app' : 'layouts::public'" :title="__('Résultats').' · '.$scrutin->titre">
    <div @class(['mx-auto max-w-5xl', 'px-4 py-12 sm:px-6 sm:py-16' => ! auth()->check()])>
        <a href="{{ auth()->user()?->estAdmin() ? route('admin.scrutins.suivi', $scrutin) : (auth()->check() ? route('espace') : route('resultats.publics')) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
            <x-icone nom="retour" class="h-4 w-4" /> {{ __('Retour') }}
        </a>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <x-etat-scrutin :scrutin="$scrutin" />
            <span class="pastille-neutre">{{ $scrutin->publicCible() }}</span>
        </div>
        <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ $scrutin->titre }}</h1>
        <p class="mt-2 text-ardoise">
            {{ __('Vote du :debut au :fin.', ['debut' => $scrutin->ouverture_at->translatedFormat('j F'), 'fin' => $scrutin->cloture_at->translatedFormat('j F Y, H\hi')]) }}
            @if ($scrutin->publie_at) {{ __('Résultats publiés le :date.', ['date' => $scrutin->publie_at->translatedFormat('j F Y')]) }} @endif
        </p>

        <p @class(['mt-6 flex items-start gap-3 rounded-lg p-4 text-sm ring-1', 'bg-bleu-50 text-bleu-900 ring-bleu-100' => ! $integrite, 'bg-alerte-50 text-alerte ring-alerte/20' => $integrite])>
            <x-icone :nom="$integrite ? 'info' : 'chaine'" class="mt-0.5 h-4 w-4" />
            <span><span class="font-semibold">{{ $integrite ? __('Urne à vérifier.') : __('Urne intègre.') }}</span> {{ $integrite ?? __('Aucun bulletin modifié, ajouté ou retiré depuis le vote : la chaîne d’empreintes est intacte.') }}</span>
        </p>

        <div class="mt-6"><x-resultats :scrutin="$scrutin" :r="$r" /></div>
    </div>
</x-dynamic-component>
