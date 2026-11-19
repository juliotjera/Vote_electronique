<x-dynamic-component :component="auth()->check() ? 'layouts::app' : 'layouts::public'" :title="__('Urne publique').' · '.$scrutin->titre">
    <div @class(['mx-auto max-w-4xl', 'px-4 py-10 sm:px-6 sm:py-14' => ! auth()->check()])
        x-data="chercherDansUrne(@js($bulletins->map(fn ($b) => [$b->rang, $b->recu_empreinte])->all()))">

        <div class="flex flex-wrap items-center gap-2">
            <x-etat-scrutin :scrutin="$scrutin" />
            <span class="pastille-neutre">{{ $scrutin->publicCible() }}</span>
        </div>
        <h1 class="mt-3 text-4xl sm:text-5xl">{{ __('Urne publique') }}</h1>
        <p class="mt-1 text-lg font-semibold">{{ $scrutin->titre }}</p>
        <p class="mt-3 max-w-2xl text-ardoise">{{ __('Chaque ligne est un bulletin déposé, scellé par une empreinte qui contient celle du bulletin précédent. Le contenu des bulletins n’apparaît nulle part : seuls le numéro et les empreintes sont publics.') }}</p>

        {{-- Retrouver son bulletin : le reçu est transformé en empreinte dans le navigateur --}}
        <form class="mt-8 rounded-xl border border-trait bg-papier p-5 shadow-carte sm:p-6" @submit.prevent="chercher()">
            <label for="recu-urne" class="libelle">{{ __('Retrouver mon bulletin avec mon reçu') }}</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input id="recu-urne" x-model="recu" class="champ empreinte uppercase tracking-[0.12em]" placeholder="ABCD-EFGH-JKLM" autocomplete="off" required>
                <button type="submit" class="btn-principal shrink-0">{{ __('Chercher dans l’urne') }}</button>
            </div>
            <p class="mt-2 text-[13px] text-brume">{{ __('Votre reçu ne quitte pas votre téléphone : son empreinte est calculée ici, puis comparée à la liste ci-dessous.') }}</p>
            <p x-show="etat === 'trouve'" x-cloak class="mt-3 font-semibold text-bleu-700" role="status">{{ __('Votre bulletin est le n°') }} <span x-text="numero"></span>. {{ __('Il est surligné dans la liste.') }}</p>
            <p x-show="etat === 'absent'" x-cloak class="erreur" role="status">{{ __('Aucun bulletin de cette urne ne correspond à ce reçu. Vérifiez le code, ou l’urne du bon scrutin.') }}</p>
        </form>

        <div class="mt-6 flex flex-wrap items-baseline justify-between gap-3 border-b border-trait pb-3">
            <p class="chiffres"><span class="font-titre text-3xl">{{ $bulletins->count() }}</span> {{ trans_choice('bulletin scellé|bulletins scellés', $bulletins->count()) }}</p>
            <p @class(['flex items-center gap-2 text-sm font-semibold', 'text-bleu-700' => ! $integrite, 'text-alerte' => $integrite])>
                <x-icone :nom="$integrite ? 'info' : 'chaine'" class="h-4 w-4" />
                {{ $integrite ? __('Chaîne rompue : :detail', ['detail' => $integrite]) : __('Chaîne d’empreintes intacte') }}
            </p>
        </div>

        <ol class="divide-y divide-trait border-b border-trait bg-papier">
            @forelse ($bulletins as $bulletin)
                <li id="bulletin-{{ $bulletin->rang }}" class="grid grid-cols-[4.5rem_1fr] gap-x-3 px-3 py-2.5 transition-colors sm:grid-cols-[5rem_1fr_1fr]"
                    :class="numero === {{ $bulletin->rang }} ? 'bg-bleu-50 outline-2 outline-bleu-700 -outline-offset-2' : ''">
                    <span class="chiffres font-titre text-lg">n° {{ str_pad($bulletin->rang, 3, '0', STR_PAD_LEFT) }}</span>
                    <span class="min-w-0">
                        <span class="block text-[11px] text-brume">{{ __('Empreinte du reçu') }}</span>
                        <span class="empreinte block truncate text-[13px]">{{ trim(chunk_split(substr($bulletin->recu_empreinte, 0, 20), 4, ' ')) }}…</span>
                    </span>
                    <span class="col-start-2 min-w-0 sm:col-start-auto">
                        <span class="block text-[11px] text-brume">{{ __('Empreinte du bulletin') }}</span>
                        <span class="empreinte block truncate text-[13px] text-ardoise">{{ trim(chunk_split(substr($bulletin->empreinte, 0, 20), 4, ' ')) }}…</span>
                    </span>
                </li>
            @empty
                <li class="px-3 py-8 text-ardoise">{{ __('L’urne est vide : aucun bulletin déposé pour l’instant.') }}</li>
            @endforelse
        </ol>
        <p class="mt-3 text-[13px] text-brume">{{ __('Empreintes SHA-256 tronquées pour la lecture. Modifier, ajouter ou retirer un bulletin change toutes les empreintes qui suivent.') }}</p>
    </div>
</x-dynamic-component>
