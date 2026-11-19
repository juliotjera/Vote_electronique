<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-tete :titre="$title ?? null" />
</head>
@php
    $user = auth()->user();
    $admin = $user->estAdmin();
    $liens = $admin
        ? [
            ['admin.tableau', 'Tableau de bord', 'tableau', 'admin.tableau'],
            ['admin.scrutins', 'Scrutins', 'urne', 'admin.scrutins*'],
            ['admin.candidatures', 'Candidatures', 'utilisateur', 'admin.candidatures'],
            ['admin.electeurs', 'Électeurs', 'utilisateurs', 'admin.electeurs'],
            ['admin.groupes', 'Groupes', 'tableau', 'admin.groupes'],
            ['admin.journal', 'Journal', 'liste', 'admin.journal'],
        ]
        : [
            ['espace', __('Mes scrutins'), 'urne', 'espace|scrutin|candidater|resultats'],
            ['mes-donnees', __('Mes données'), 'utilisateur', 'mes-donnees'],
        ];
    $nonLues = $admin ? 0 : $user->unreadNotifications()->count();
    $dernieres = $admin ? collect() : $user->notifications()->latest()->limit(5)->get();
@endphp
<body class="min-h-screen" x-data="{ menu: false }">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">{{ __('Aller au contenu') }}</a>
    <header @class(['sticky top-0 z-40 border-b', 'fond-admin border-white/10 text-white' => $admin, 'border-trait bg-white/95 backdrop-blur' => ! $admin])>
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6">
            <a href="{{ route($liens[0][0]) }}" wire:navigate class="mr-2 shrink-0"><x-logo :clair="$admin" compact /></a>

            <nav class="hidden flex-1 items-center gap-1 text-sm font-medium lg:flex" aria-label="{{ __('Navigation') }}">
                @foreach ($liens as [$route, $libelle, $icone, $motif])
                    @php $actif = request()->routeIs(...explode('|', $motif)); @endphp
                    <a href="{{ route($route) }}" wire:navigate @class([
                        'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-2.5 py-2 transition',
                        'bg-white/12 text-white' => $admin && $actif,
                        'text-white/70 hover:text-white' => $admin && ! $actif,
                        'bg-bleu-50 text-bleu-700' => ! $admin && $actif,
                        'text-ardoise hover:text-encre' => ! $admin && ! $actif,
                    ]) @if ($actif) aria-current="page" @endif>
                        <x-icone :nom="$icone" class="h-4 w-4" /> {{ $libelle }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                @unless ($admin)
                    <x-choix-langue class="hidden sm:inline-flex" />

                    {{-- Cloche : les cinq dernières notifications, sans quitter la page --}}
                    <div class="relative" x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape.window="ouvert = false">
                        <button type="button" @click="ouvert = !ouvert" :aria-expanded="ouvert" aria-haspopup="true"
                            class="relative rounded-lg p-2 text-ardoise hover:bg-fond hover:text-encre"
                            aria-label="{{ $nonLues ? __('Notifications (:n non lues)', ['n' => $nonLues]) : __('Notifications') }}">
                            <x-icone nom="cloche" />
                            @if ($nonLues)
                                <span class="chiffres absolute -right-0.5 -top-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-bleu-600 px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $nonLues }}</span>
                            @endif
                        </button>
                        <div x-show="ouvert" x-cloak x-transition.origin.top.right
                            class="absolute right-0 top-12 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-lg border border-trait bg-white text-encre shadow-feuille">
                            <p class="border-b border-trait px-4 py-3 font-titre font-semibold">{{ __('Notifications') }}</p>
                            <ul class="max-h-80 divide-y divide-trait overflow-y-auto">
                                @forelse ($dernieres as $notification)
                                    @php [$titreNotif, $texteNotif] = \App\Support\TexteNotification::pour($notification); @endphp
                                    <li>
                                        <a href="{{ isset($notification->data['scrutin_id']) ? route('scrutin', $notification->data['scrutin_id']) : route('notifications') }}" wire:navigate
                                            class="flex gap-3 px-4 py-3 hover:bg-fond">
                                            <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-bleu-600' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold">{{ $titreNotif }}</span>
                                                <span class="line-clamp-2 block text-xs text-ardoise">{{ $texteNotif }}</span>
                                                <span class="mt-0.5 block text-[11px] text-brume">{{ $notification->created_at->diffForHumans() }}</span>
                                            </span>
                                        </a>
                                    </li>
                                @empty
                                    <li class="px-4 py-8 text-center text-sm text-ardoise">{{ __('Aucune notification pour l’instant.') }}</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('notifications') }}" wire:navigate class="block border-t border-trait px-4 py-3 text-center text-sm font-semibold text-bleu-700 hover:bg-bleu-50">{{ __('Tout voir') }}</a>
                        </div>
                    </div>
                @endunless

                <span class="hidden text-right text-xs leading-tight sm:block lg:hidden xl:block">
                    <span class="block font-semibold">{{ $user->nomComplet() }}</span>
                    <span @class(['block', 'text-white/60' => $admin, 'text-brume' => ! $admin])>
                        {{ $admin ? 'Administration' : ($user->classe?->nom ? __('Électeur').' · '.$user->classe->nom : __('Électeur')) }}
                    </span>
                </span>
                <form method="POST" action="{{ route('deconnexion') }}">
                    @csrf
                    <button type="submit" @class(['rounded-lg p-2', 'text-white/70 hover:bg-white/10 hover:text-white' => $admin, 'text-ardoise hover:bg-fond hover:text-encre' => ! $admin]) aria-label="{{ __('Se déconnecter') }}" title="{{ __('Se déconnecter') }}">
                        <x-icone nom="sortie" />
                    </button>
                </form>
                <button type="button" class="rounded-lg p-2 lg:hidden" @click="menu = !menu" :aria-expanded="menu" aria-label="{{ __('Menu') }}">
                    <x-icone nom="menu" />
                </button>
            </div>
        </div>
        <nav x-show="menu" x-cloak x-transition.opacity class="border-t border-current/10 px-4 pb-4 lg:hidden" aria-label="{{ __('Navigation') }}">
            @foreach ($liens as [$route, $libelle, $icone])
                <a href="{{ route($route) }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium">
                    <x-icone :nom="$icone" class="h-4 w-4" /> {{ $libelle }}
                </a>
            @endforeach
            @unless ($admin)
                <a href="{{ route('notifications') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium">
                    <x-icone nom="cloche" class="h-4 w-4" /> {{ __('Notifications') }}
                </a>
                <x-choix-langue class="mt-2 sm:hidden" />
            @endunless
        </nav>
    </header>

    <main id="contenu" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <x-messages />
        {{ $slot }}
    </main>
    {{-- Alpine (bulle, menu, comptes à rebours) aussi sur les pages qui ne sont pas des composants Livewire --}}
    @livewireScripts
</body>
</html>
