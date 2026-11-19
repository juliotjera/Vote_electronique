<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-20" x-data="recuDepuisAdresse">
    <span class="fond-tampon inline-flex h-12 w-12 items-center justify-center rounded-lg text-white"><x-icone nom="recu" /></span>
    <h1 class="mt-5 text-3xl font-bold sm:text-4xl">{{ __('Vérifier mon reçu') }}</h1>
    <p class="mt-3 text-ardoise">{{ __('Après votre vote, un reçu de 12 caractères vous a été remis. Il prouve que votre bulletin est dans l’urne et qu’il est compté, sans jamais révéler pour qui vous avez voté : personne, pas même l’administration, ne peut relier ce reçu à votre choix.') }}</p>

    <form wire:submit="verifier" class="carte mt-8 p-5 sm:p-6">
        <label for="recu" class="libelle">{{ __('Code du reçu') }}</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input id="recu" wire:model="recu" class="champ chiffres uppercase tracking-[0.2em]" placeholder="ABCD-EFGH-JKLM" autocomplete="off" required>
            <button type="submit" class="btn-principal shrink-0">{{ __('Vérifier') }}</button>
        </div>
        @error('recu') <p class="erreur">{{ $message }}</p> @enderror
        <p class="mt-3 text-xs text-brume">{{ __('Vous avez téléchargé votre reçu ? Scannez son code QR avec l’appareil photo du téléphone : la vérification se lance seule.') }}</p>
    </form>

    @if ($resultat)
        <div class="mt-6" role="status">
            @if ($resultat['trouve'])
                <div class="fond-nuit rounded-lg p-6 text-white shadow-feuille">
                    <p class="flex items-center gap-2 font-titre text-xl font-semibold"><x-icone nom="bouclier" class="text-bleu-300" /> {{ __('Votre bulletin est dans l’urne') }}</p>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-white/55">{{ __('Scrutin') }}</dt><dd class="font-medium">{{ $resultat['scrutin'] }}</dd></div>
                        <div><dt class="text-white/55">{{ __('Étape') }}</dt><dd class="font-medium">{{ $resultat['etat'] }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-white/55">{{ __('Intégrité de l’urne') }}</dt>
                            <dd class="font-medium">{{ $resultat['integre'] ? __('Vérifiée : aucun bulletin modifié, ajouté ou retiré depuis le vote.') : __('Anomalie détectée : l’administration a été alertée.') }}</dd></div>
                    </dl>
                </div>
            @else
                <div class="rounded-lg border border-alerte/20 bg-alerte-50 p-5 text-sm text-alerte">
                    {{ __('Aucun bulletin ne correspond à ce reçu. Vérifiez le code : il ne contient jamais les caractères 0, 1, O ni I.') }}
                </div>
            @endif
        </div>
    @endif
</div>
