<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 36px 44px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #0f172a; line-height: 1.5; }
    .bandeau { background: #1e3a8a; color: #fff; padding: 20px 24px; border-radius: 8px; }
    .bandeau h1 { margin: 0; font-size: 21px; }
    .bandeau p { margin: 5px 0 0; color: #bfdbfe; }
    h2 { font-size: 15px; color: #0b1f4d; margin: 22px 0 2px; }
    .pour { color: #1d4ed8; font-size: 9.5px; font-weight: bold; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    td { vertical-align: top; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; }
    .num { width: 22px; color: #1d4ed8; font-weight: bold; }
    .titre { width: 128px; font-weight: bold; }
    .pied { margin-top: 26px; color: #586679; font-size: 9px; }
    .section { page-break-inside: avoid; }
</style>
</head>
<body>
    <div class="bandeau">
        <h1>Guide d’utilisation · Vote électronique</h1>
        <p>Élections étudiantes : voter, vérifier son vote, se porter candidat, organiser un scrutin</p>
    </div>

    @foreach ($sections as $section)
        <div class="section">
            <h2>{{ $section['titre'] }}</h2>
            <p class="pour">{{ $section['pour'] }}</p>
            <table>
                @foreach ($section['etapes'] as $i => [$titre, $texte])
                    <tr><td class="num">{{ $i + 1 }}</td><td class="titre">{{ $titre }}</td><td>{{ $texte }}</td></tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <p class="pied">Version en ligne : {{ route('aide') }} · Document généré le {{ now()->translatedFormat('j F Y') }}.</p>
</body>
</html>
