<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#123a63">
    <title>Vérification de vignette - {{ config('app.name') }}</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fonts.css') }}" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; font-family: 'Inter', system-ui, sans-serif; background: linear-gradient(160deg, #061a2e, #123a63); display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .box { width: 100%; max-width: 440px; background: #fff; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 70px -20px rgba(0, 0, 0, .55); }
        .top { padding: 1.6rem 1.5rem 1.2rem; text-align: center; color: #fff; }
        .top i { font-size: 3.4rem; }
        .top h1 { font-size: 1.25rem; font-weight: 800; margin: .3rem 0 0; color: #fff; }
        .top p { margin: .2rem 0 0; opacity: .9; font-size: .85rem; }
        .top.valid { background: linear-gradient(135deg, #22a559, #12793d); }
        .top.expired { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .top.pending { background: linear-gradient(135deg, #64748b, #475569); }
        .top.unknown { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: .55rem 1rem; margin: 0; padding: 1.3rem 1.5rem; font-size: .9rem; }
        dt { color: #6b7a90; font-weight: 500; }
        dd { margin: 0; text-align: right; font-weight: 700; color: #1f2a44; }
        .plate { font-size: 1.4rem; letter-spacing: .06em; color: #123a63; }
        .foot { padding: .9rem 1.5rem 1.3rem; text-align: center; font-size: .72rem; color: #8a97ab; }
    </style>
</head>
<body>
    <main class="box">
        @if ($state === 'unknown')
            <div class="top unknown">
                <i class='bx bx-x-circle'></i>
                <h1>Vignette non reconnue</h1>
                <p>Ce QR Code ne correspond à aucune vignette délivrée par {{ config('app.name') }}.</p>
            </div>
            <div class="foot">Si vous pensez à une erreur, contactez votre mairie. Une vignette non reconnue peut être une contrefaçon.</div>
        @else
            <div class="top {{ $state }}">
                @if ($state === 'valid')
                    <i class='bx bx-check-circle'></i><h1>Vignette valide</h1><p>Valable jusqu'au 31/12/{{ $demande->vgt_year }}</p>
                @elseif ($state === 'expired')
                    <i class='bx bx-time-five'></i><h1>Vignette expirée</h1><p>Valable jusqu'au 31/12/{{ $demande->vgt_year }}</p>
                @else
                    <i class='bx bx-hourglass'></i><h1>Vignette non délivrée</h1><p>La demande existe mais le paiement n'est pas encore confirmé.</p>
                @endif
            </div>
            <dl>
                <dt>Matricule</dt><dd class="plate">{{ $demande->moto->plate_number }}</dd>
                <dt>Genre</dt><dd>{{ $demande->moto->type_or_brand }}</dd>
                <dt>Année</dt><dd>{{ $demande->vgt_year }}</dd>
                <dt>Mairie</dt><dd>{{ $demande->mairie->name }}</dd>
                <dt>Référence</dt><dd>{{ $reference }}</dd>
            </dl>
            <div class="foot">Vérification faite le {{ now()->format('d/m/Y à H:i') }}. Aucune donnée personnelle n'est affichée.</div>
        @endif
    </main>
</body>
</html>
