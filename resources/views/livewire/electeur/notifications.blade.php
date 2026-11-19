<div class="mx-auto max-w-3xl">
    <h1 class="text-3xl font-bold">{{ __('Notifications') }}</h1>
    <p class="mt-1 text-ardoise">{{ __('Ouverture des votes, rappels, résultats et décisions sur vos candidatures. Elles vous sont aussi envoyées par e-mail.') }}</p>

    <ul class="mt-6 space-y-3">
        @forelse ($notifications as $notification)
            @php [$titre, $texte] = \App\Support\TexteNotification::pour($notification); @endphp
            <li class="carte flex gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-bleu-50 text-bleu-700"><x-icone nom="cloche" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-semibold">{{ $titre }}</p>
                        <time class="text-xs text-brume" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                    </div>
                    <p class="mt-0.5 text-sm text-ardoise">{{ $texte }}</p>
                    @if (isset($notification->data['scrutin_id']))
                        <a href="{{ route('scrutin', $notification->data['scrutin_id']) }}" wire:navigate class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-bleu-700 hover:underline">{{ __('Ouvrir le scrutin') }} <x-icone nom="fleche" class="h-3.5 w-3.5" /></a>
                    @endif
                </div>
            </li>
        @empty
            <li class="carte p-10 text-center text-ardoise">{{ __('Aucune notification pour l’instant.') }}</li>
        @endforelse
    </ul>
</div>
