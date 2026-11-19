<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 32px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    .bandeau { background: #0b1f4d; color: #fff; padding: 18px 22px; border-radius: 8px; }
    .bandeau h1 { margin: 0; font-size: 20px; }
    .bandeau p { margin: 4px 0 0; color: #93c5fd; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    th { text-align: left; font-size: 10px; color: #586679; border-bottom: 1px solid #e2e8f0; padding: 6px 4px; }
    td { padding: 7px 4px; border-bottom: 1px solid #e2e8f0; }
    .barre { height: 7px; background: #1d4ed8; border-radius: 4px; }
    .chiffres td { width: 25%; }
    .valeur { font-size: 18px; font-weight: bold; }
    .elu { color: #1e40af; font-weight: bold; }
    .note { color: #586679; font-size: 9.5px; margin-top: 16px; }
    .signatures td { height: 70px; vertical-align: top; border: 1px solid #e2e8f0; }
</style>
</head>
<body>
    <div class="bandeau">
        <h1>Procès-verbal des résultats</h1>
        <p>{{ $scrutin->titre }} · {{ $scrutin->publicCible() }}</p>
    </div>
    <p>Vote du {{ $scrutin->ouverture_at->translatedFormat('j F Y à H\hi') }} au {{ $scrutin->cloture_at->translatedFormat('j F Y à H\hi') }} · {{ $scrutin->libelleType() }} · {{ $scrutin->nombre_elus }} siège(s)</p>

    <table class="chiffres">
        <tr>
            <td><div>Inscrits</div><div class="valeur">{{ $r['inscrits'] }}</div></td>
            <td><div>Votants</div><div class="valeur">{{ $r['votants'] }}</div></td>
            <td><div>Participation</div><div class="valeur">{{ number_format($r['participation'], 1, ',', ' ') }} %</div></td>
            <td><div>Votes blancs</div><div class="valeur">{{ $r['blancs'] }}</div></td>
        </tr>
    </table>

    <table>
        <thead><tr><th>Rang</th><th>Candidat</th><th>Matricule</th><th>Voix</th><th>%</th><th style="width:28%"></th><th>Résultat</th></tr></thead>
        <tbody>
            @foreach ($r['candidats'] as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="{{ $c->elu ? 'elu' : '' }}">{{ $c->user->nomComplet() }}</td>
                    <td>{{ $c->user->matricule }}</td>
                    <td>{{ $c->voix }}</td>
                    <td>{{ number_format($c->pourcentage, 1, ',', ' ') }}</td>
                    <td><div class="barre" style="width: {{ max(2, $c->pourcentage) }}%"></div></td>
                    <td>{{ $c->elu ? 'Élu' : ($c->exAequo ? 'Égalité' : '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="note">
        Intégrité de l’urne : {{ $integrite ?? 'vérifiée, chaîne d’empreintes SHA-256 intacte ('.$r['votants'].' bulletins pour '.$r['votants'].' votants émargés)' }}.<br>
        @if ($r['egalite'])Égalité pour le dernier siège : départage selon le règlement de l’établissement.<br>@endif
        Pourcentages calculés sur les bulletins exprimés. Document généré le {{ now()->translatedFormat('j F Y à H\hi') }}.
    </p>

    <table class="signatures">
        <tr><td>Le président du bureau de vote</td><td>Les assesseurs</td></tr>
    </table>
</body>
</html>
