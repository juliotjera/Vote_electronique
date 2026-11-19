<div class="mx-auto max-w-3xl">
    <a href="{{ route('scrutin', $scrutin) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-ardoise hover:text-encre">
        <x-icone nom="retour" class="h-4 w-4" /> {{ $scrutin->titre }}
    </a>
    <h1 class="mt-4 text-3xl font-bold">{{ __('Ma candidature') }}</h1>
    <p class="mt-1 text-ardoise">
        @if ($scrutin->candidaturesOuvertes())
            {{ __('Dépôt possible jusqu’au :date. Votre candidature sera présentée aux électeurs après validation.', ['date' => ($scrutin->candidatures_fin_at ?? $scrutin->ouverture_at)->translatedFormat('l j F, H\hi')]) }}
        @else
            {{ __('Le dépôt des candidatures est clos pour ce scrutin.') }}
        @endif
    </p>

    @if ($candidature)
        <div @class([
            'mt-6 flex items-start gap-3 rounded-lg p-4 text-sm ring-1',
            'bg-bleu-50 text-bleu-900 ring-bleu-100' => $candidature->statut !== 'rejetee',
            'bg-alerte-50 text-alerte ring-alerte/20' => $candidature->statut === 'rejetee',
        ])>
            <x-icone :nom="$candidature->statut === 'validee' ? 'coche' : 'info'" class="mt-0.5 h-4 w-4" />
            <p><span class="font-semibold">{{ $candidature->libelleStatut() }}.</span>
                @if ($candidature->statut === 'validee') {{ __('Elle est visible par les électeurs.') }}
                @elseif ($candidature->statut === 'rejetee') {{ __('Motif : :motif', ['motif' => $candidature->motif_rejet]) }} {{ __('Vous pouvez la corriger et la renvoyer.') }}
                @else {{ __('L’administration l’examinera avant l’ouverture du vote.') }} @endif
            </p>
        </div>
    @endif

    <form wire:submit="enregistrer" class="carte mt-6 space-y-6 p-6 sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            @if ($photo)
                <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Aperçu de la photo') }}" class="h-24 w-24 rounded-lg object-cover">
            @elseif ($candidature)
                <x-avatar :candidature="$candidature" taille="h-24 w-24 text-2xl" />
            @else
                <x-avatar :user="auth()->user()" taille="h-24 w-24 text-2xl" />
            @endif
            <div>
                <label for="photo" class="libelle">{{ __('Photo (facultative)') }}</label>
                <input id="photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" @disabled(! $modifiable)
                    class="block text-sm text-ardoise file:mr-3 file:rounded-lg file:border-0 file:bg-bleu-50 file:px-4 file:py-2 file:font-semibold file:text-bleu-700">
                <p class="mt-1 text-xs text-brume">{{ __('JPEG, PNG ou WebP, 3 Mo maximum. Recadrée en carré.') }}</p>
                <div wire:loading wire:target="photo" class="mt-1 text-xs text-bleu-700">{{ __('Envoi de la photo…') }}</div>
                @error('photo') <p class="erreur">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="slogan" class="libelle">{{ __('Slogan') }}</label>
            <input id="slogan" wire:model="slogan" class="champ" maxlength="120" placeholder="{{ __('Pour une voix étudiante forte !') }}" @disabled(! $modifiable) required>
            @error('slogan') <p class="erreur">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="programme" class="libelle">{{ __('Programme') }}</label>
            <textarea id="programme" wire:model="programme" rows="8" maxlength="3000" class="champ" placeholder="{{ __('Vos engagements, un par ligne.') }}" @disabled(! $modifiable) required></textarea>
            <p class="mt-1 text-xs text-brume">{{ __('Un engagement par ligne : les deux premiers s’affichent sur votre carte de candidat. Entre 30 et 3 000 caractères.') }}</p>
            @error('programme') <p class="erreur">{{ $message }}</p> @enderror
        </div>

        @if ($modifiable)
            <div class="flex flex-col-reverse gap-3 border-t border-trait pt-6 sm:flex-row sm:justify-between">
                @if ($candidature)
                    <button type="button" wire:click="retirer" wire:confirm="{{ __('Retirer votre candidature ?') }}" class="btn-danger">{{ __('Retirer ma candidature') }}</button>
                @else
                    <span></span>
                @endif
                <button type="submit" class="btn-principal" wire:loading.attr="disabled">{{ $candidature ? __('Renvoyer ma candidature') : __('Déposer ma candidature') }}</button>
            </div>
        @endif
    </form>
</div>
