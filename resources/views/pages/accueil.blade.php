@php
    // le bulletin affiché dans le téléphone : celui du scrutin ouvert, sinon un exemple
    $exemple = $vitrine?->candidatures->take(3)->map(fn ($c) => [$c->user->initiales(), $c->user->nomComplet(), $c->slogan])->values()->all()
        ?: [['RA', 'Aina RAKOTO', __('Une promo unie, une voix entendue')], ['NH', 'Hery NIRINA', __('Moins de stress, plus de réussite')], ['TF', 'Fara TOJO', __('Le numérique au service de la promo')]];
    $aplats = [['#dde3fa', '#0d1b5e'], ['#1b3bb3', '#ffffff'], ['#000000', '#ffffff']];
@endphp
<x-layouts::public :title="__('Accueil')">
    {{-- ─── Ouverture : la promesse à gauche, le vrai écran de vote à droite ─── --}}
    <section class="overflow-hidden border-b border-trait">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-4 pb-16 pt-10 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:pb-24 lg:pt-16">
            <div>
                @if ($vitrine)
                    <a href="{{ route('urne', $vitrine) }}" wire:navigate class="inline-flex max-w-full items-center gap-2.5 rounded-full border border-trait bg-papier py-1.5 pl-2.5 pr-3.5 text-sm hover:border-trait-fort">
                        <span class="relative flex h-2 w-2 shrink-0"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-bleu-500 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-bleu-700"></span></span>
                        <span class="truncate"><span class="font-semibold">{{ __('Vote en cours') }}</span> <span class="text-ardoise">{{ $vitrine->titre }}</span></span>
                    </a>
                @endif
                <h1 class="mt-6 max-w-[21ch] text-[38px] leading-[1.06] sm:text-[52px]">{{ __('Les élections étudiantes, depuis le téléphone, vérifiables par chacun.') }}</h1>
                <p class="mt-6 max-w-[50ch] text-lg leading-relaxed text-ardoise">{{ __('Délégués de promotion et bureau des étudiants : l’électeur s’identifie avec un code reçu par e-mail, vote en une minute et garde un reçu qui retrouve son bulletin dans l’urne publique.') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    {{-- choix du rôle dès l'accueil --}}
                    <a href="{{ route('connexion', ['espace' => 'electeur']) }}" wire:navigate class="btn-principal min-h-12 px-6 text-base"><x-icone nom="urne" class="h-5 w-5" /> {{ __('Je vote : espace électeur') }}</a>
                    <a href="{{ route('connexion', ['espace' => 'administration']) }}" wire:navigate class="btn-contour min-h-12 px-6 text-base"><x-icone nom="tableau" class="h-5 w-5" /> {{ __('J’organise : espace administration') }}</a>
                </div>
                <ul class="mt-9 flex flex-col gap-2.5 text-[15px] sm:flex-row sm:flex-wrap sm:gap-x-6">
                    @foreach ([__('Un électeur, un seul vote'), __('Choix invisible, même pour l’admin'), __('Résultats dès la clôture')] as $garantie)
                        <li class="flex items-center gap-2 whitespace-nowrap"><x-icone nom="coche" class="h-4 w-4 text-bleu-700" />{{ $garantie }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Aperçu : l'écran de vote tel que l'électeur le voit, et le reçu remis après le vote --}}
            <div class="relative mx-auto w-full max-w-[440px] lg:mr-0" aria-hidden="true">
                <div class="absolute inset-x-0 bottom-6 top-14 rounded-[32px] fond-ciel sm:-inset-x-6"></div>
                <div class="relative mx-auto w-[288px] rounded-[46px] bg-nuit p-[10px] shadow-feuille">
                    <div class="overflow-hidden rounded-[37px] bg-papier">
                        <div class="flex items-center justify-between px-6 pb-1 pt-3 text-[11px] font-semibold">
                            <span>08:41</span><span class="h-[18px] w-[76px] rounded-full bg-nuit"></span><span class="chiffres">4G</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-trait px-4 py-2.5">
                            <x-logo compact class="origin-left scale-[0.8]" />
                            <span class="rounded bg-fond px-1.5 py-0.5 text-[10px] font-semibold">FR</span>
                        </div>
                        <div class="px-4 pb-5 pt-4">
                            <span class="pastille-bleu text-[10px]">{{ __('Vote ouvert') }}</span>
                            <p class="font-titre mt-2 text-[17px] leading-tight">{{ $vitrine?->titre ?? __('Élection des délégués L2') }}</p>
                            <p class="mt-1 text-[11px] text-brume">{{ __('Cochez un seul nom, puis relisez votre bulletin.') }}</p>
                            <ul class="mt-3 space-y-2">
                                @foreach ($exemple as $i => [$initiales, $nom, $slogan])
                                    <li @class(['flex items-center gap-2.5 rounded-lg border p-2.5', 'border-bleu-700 bg-bleu-50 outline outline-1 outline-bleu-700' => $i === 0, 'border-trait' => $i !== 0])>
                                        <span class="font-titre inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold" style="background: {{ $aplats[$i][0] }}; color: {{ $aplats[$i][1] }}">{{ $initiales }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[12px] font-semibold">{{ $nom }}</span>
                                            <span class="block truncate text-[10.5px] text-brume">« {{ $slogan }} »</span>
                                        </span>
                                        <span @class(['inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border-[1.5px]', 'border-bleu-700 bg-bleu-700 text-white' => $i === 0, 'border-trait-fort' => $i !== 0])>
                                            @if ($i === 0)<x-icone nom="coche" class="h-3.5 w-3.5" />@endif
                                        </span>
                                    </li>
                                @endforeach
                                <li class="flex items-center justify-between rounded-lg border border-dashed border-trait-fort px-2.5 py-2 text-[11px] text-ardoise">{{ __('Voter blanc') }}<span class="h-5 w-5 rounded border-[1.5px] border-trait-fort"></span></li>
                            </ul>
                            <span class="mt-4 flex h-10 items-center justify-center rounded-md bg-bleu-700 text-[12.5px] font-semibold text-white">{{ __('Relire mon bulletin') }}</span>
                        </div>
                    </div>
                </div>

                {{-- le reçu, posé contre le téléphone --}}
                <div class="guilloche absolute -left-1 -bottom-10 w-[190px] -rotate-[4deg] rounded-md border border-trait-fort bg-papier p-3.5 shadow-feuille sm:-left-14 sm:-bottom-8">
                    <p class="text-[10px] font-semibold text-brume">{{ __('Reçu de vote') }}</p>
                    <p class="empreinte mt-1 text-[15px] font-semibold tracking-[0.08em]">K7QF-2MXP-9TRA</p>
                    <p class="mt-1.5 text-[10px] leading-snug text-ardoise">{{ __('Retrouvez votre bulletin dans l’urne publique.') }}</p>
                    <span class="font-titre absolute -right-3 -top-4 rotate-[-12deg] rounded border-2 border-bleu-700 bg-papier/90 px-2 py-0.5 text-[13px] font-extrabold text-bleu-700">{{ __('A VOTÉ') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── L'urne en direct : la preuve, pas une promesse ─── --}}
    <section class="bg-fond">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_1.25fr] lg:items-center lg:gap-16 lg:py-20">
            <div>
                <h2 class="text-3xl sm:text-4xl">{{ __('Une urne publique, que chacun peut ouvrir') }}</h2>
                <p class="mt-4 max-w-[48ch] text-ardoise">{{ __('Chaque bulletin déposé apparaît ici avec un numéro et une empreinte, jamais avec un nom ni un choix. Chaque empreinte contient celle du bulletin précédent : retirer ou modifier un bulletin après coup se verrait immédiatement.') }}</p>
                <p class="mt-4 max-w-[48ch] text-ardoise">{{ __('Avec son reçu, l’électeur retrouve son propre bulletin dans la liste. Le calcul se fait sur son téléphone : le reçu n’est jamais envoyé.') }}</p>
            </div>
            <livewire:urne-en-direct />
        </div>
    </section>

    {{-- ─── Voter : une vraie séquence ─── --}}
    <section class="bg-papier">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
            <h2 class="text-3xl sm:text-4xl">{{ __('Voter prend une minute') }}</h2>
            <p class="mt-3 max-w-[60ch] text-ardoise">{{ __('Pas de mot de passe, pas d’application à installer, une page légère qui se charge même avec peu de réseau.') }}</p>
            <ol class="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                @foreach ([
                    [__('Je m’identifie'), __('Matricule et e-mail, puis le code à 6 chiffres reçu par e-mail.')],
                    [__('Je lis les candidatures'), __('Photo, slogan et engagements de chaque candidat validé.')],
                    [__('Je coche et je relis'), __('Mon bulletin s’affiche une dernière fois avant d’entrer dans l’urne.')],
                    [__('Je garde mon reçu'), __('Un code et un QR code pour retrouver mon bulletin dans l’urne publique.')],
                ] as $i => [$titre, $texte])
                    <li class="relative">
                        @unless ($loop->last)<span class="absolute left-12 right-0 top-5 hidden h-px bg-trait lg:block" aria-hidden="true"></span>@endunless
                        <span class="chiffres relative inline-flex h-10 w-10 items-center justify-center rounded-full border-[1.5px] border-bleu-700 bg-papier font-semibold text-bleu-700">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg">{{ $titre }}</h3>
                        <p class="mt-1.5 text-[15px] text-ardoise">{{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ─── Ce que l'électeur peut vérifier lui-même : les inquiétudes du terrain, et la réponse ─── --}}
    <section id="verifier" class="fond-nuit scroll-mt-16 text-white">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_1.6fr] lg:py-20">
            <div>
                <h2 class="text-3xl sm:text-4xl">{{ __('Ce que vous pouvez vérifier vous-même') }}</h2>
                <p class="mt-4 max-w-[44ch] text-white/70">{{ __('Les inquiétudes exprimées par les étudiants, l’administration et les candidats, et la réponse technique apportée à chacune.') }}</p>
            </div>
            <dl class="divide-y divide-white/15 border-y border-white/15">
                @foreach ([
                    [__('« Quelqu’un peut voter deux fois. »'), __('La base de données refuse un second émargement pour le même électeur et le même scrutin, même si deux votes arrivent à la même seconde.')],
                    [__('« On saura pour qui j’ai voté. »'), __('La liste d’émargement (qui a voté) et l’urne (ce qui a été voté) ne partagent aucune colonne : pas d’identifiant commun, pas d’heure de vote.')],
                    [__('« Mon vote ne sera pas compté. »'), __('Votre reçu retrouve votre bulletin dans l’urne publique. Le calcul se fait sur votre téléphone : votre reçu n’est jamais envoyé.')],
                    [__('« Les résultats seront arrangés. »'), __('Chaque bulletin porte l’empreinte du précédent : modifier, ajouter ou retirer un bulletin après coup casse la chaîne, et l’anomalie s’affiche.')],
                ] as [$crainte, $reponse])
                    <div class="grid gap-2 py-6 sm:grid-cols-[minmax(0,15rem)_1fr] sm:gap-8">
                        <dt class="font-titre text-xl text-white">{{ $crainte }}</dt>
                        <dd class="text-white/75">{{ $reponse }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ─── Pour l'organisation ─── --}}
    <section class="bg-papier">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_1.6fr] lg:py-20">
            <div>
                <h2 class="text-3xl sm:text-4xl">{{ __('Pour l’administration et le BDE') }}</h2>
                <p class="mt-4 max-w-[44ch] text-ardoise">{{ __('Fini les affiches arrachées, le dépouillement de plusieurs heures et les listes d’absents remplies à leur place.') }}</p>
                <a href="{{ route('resultats.publics') }}" wire:navigate class="btn-sombre mt-8">{{ __('Voir des résultats publiés') }}{{ $publies ? " ($publies)" : '' }}</a>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['utilisateurs', __('Listes électorales par CSV'), __('Import du fichier de la scolarité, scrutins réservés à une ou plusieurs classes.')],
                    ['fichier', __('Candidatures en ligne'), __('Photo, slogan, programme, validés ou refusés avec un motif.')],
                    ['graphique', __('Résultats immédiats'), __('Dépouillement automatique à la clôture, procès-verbal PDF et export CSV.')],
                    ['cadenas', __('Données supprimées à 30 jours'), __('La liste d’émargement disparaît automatiquement après le scrutin.')],
                ] as [$icone, $titre, $texte])
                    <div class="rounded-lg border border-trait p-5">
                        <x-icone :nom="$icone" class="h-5 w-5 text-bleu-700" />
                        <dt class="mt-3 font-semibold">{{ $titre }}</dt>
                        <dd class="mt-1 text-[15px] text-ardoise">{{ $texte }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</x-layouts::public>
