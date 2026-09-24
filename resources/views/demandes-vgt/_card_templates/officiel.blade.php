{{--
    Modèle « Officiel » (App\Enums\VgtCardTemplate::Officiel) — couleurs et sceau évoquant les armoiries du
    Mali. Le sceau ci-dessous est un dessin original (étoile + cercles), pas une reproduction des armoiries
    réelles de l'État : PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT / la DGI avant toute mise en
    production (CLAUDE.md §5), risque de contrefaçon d'un symbole d'État sinon.
--}}
<div style="font-family: Georgia, 'Times New Roman', serif; max-width: 380px; margin: 24px auto; border: 1px solid #14b53a; border-radius: 6px; overflow: hidden; color: #1a1a1a;">
    <div style="display: flex; height: 8px;">
        <div style="flex: 1; background: #14b53a;"></div>
        <div style="flex: 1; background: #fcd116;"></div>
        <div style="flex: 1; background: #ce1126;"></div>
    </div>
    <div style="padding: 16px 20px 4px; text-align: center;">
        <svg width="56" height="56" viewBox="0 0 56 56" style="margin-bottom: 4px;">
            <circle cx="28" cy="28" r="26" fill="#fff" stroke="#fcd116" stroke-width="3"/>
            <circle cx="28" cy="28" r="20" fill="none" stroke="#14b53a" stroke-width="2"/>
            <path d="M28 14 L31.5 24.5 L42.5 24.5 L33.5 31 L37 41.5 L28 35 L19 41.5 L22.5 31 L13.5 24.5 L24.5 24.5 Z" fill="#ce1126"/>
        </svg>
        <div style="font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #123a63;">République du Mali</div>
        <div style="font-size: 10px; font-style: italic; color: #666; margin-bottom: 6px;">Un Peuple &mdash; Un But &mdash; Une Foi</div>
        <div style="font-size: 15px; font-weight: bold; letter-spacing: 0.5px;">CARTE DE VIGNETTE — VGT {{ $demande->vgt_year }}</div>
    </div>
    <div style="padding: 10px 20px 18px; font-size: 13px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr><td style="padding: 3px 0; color: #555;">Matricule</td><td style="padding: 3px 0; text-align: right; font-weight: bold;">{{ $demande->moto->plate_number }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Propriétaire</td><td style="padding: 3px 0; text-align: right;">{{ $proprietaire->fullName() }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Numéro</td><td style="padding: 3px 0; text-align: right;">{{ $proprietaire->phone }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Adresse</td><td style="padding: 3px 0; text-align: right;">{{ $proprietaire->address ?? '—' }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Contact urgence</td><td style="padding: 3px 0; text-align: right;">{{ $proprietaire->emergency_contact ?? '—' }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Mairie de retrait</td><td style="padding: 3px 0; text-align: right;">{{ $demande->mairie->name }}</td></tr>
            <tr><td style="padding: 3px 0; color: #555;">Date de retrait</td><td style="padding: 3px 0; text-align: right;">{{ $demande->retrait_date?->format('d/m/Y') ?? date('d/m/Y') }}</td></tr>
        </table>
    </div>
    <div style="background: #123a63; color: #fff; text-align: center; padding: 6px; font-size: 10px;">VigiMoto — Plateforme nationale de numérisation des vignettes</div>
</div>
