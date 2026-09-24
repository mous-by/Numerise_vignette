{{-- Modèle « Premium » (App\Enums\VgtCardTemplate::Premium) — fond sombre, liseré doré, style soigné. --}}
<div style="font-family: Georgia, 'Times New Roman', serif; max-width: 380px; margin: 24px auto; background: #0f2942; color: #f2e6c9; border: 1px solid #d4af37; border-radius: 8px; overflow: hidden;">
    <div style="border: 1px solid #d4af37; margin: 10px; border-radius: 4px; padding: 16px;">
        <div style="text-align: center; border-bottom: 1px solid #d4af37; padding-bottom: 10px; margin-bottom: 12px;">
            <div style="font-size: 20px; letter-spacing: 2px; color: #d4af37;">VIGIMOTO</div>
            <div style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; opacity: 0.8;">Carte VGT {{ $demande->vgt_year }}</div>
        </div>
        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <tr><td style="padding: 4px 0; color: #b9a87a;">Matricule</td><td style="padding: 4px 0; text-align: right; color: #d4af37; font-weight: bold;">{{ $demande->moto->plate_number }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Propriétaire</td><td style="padding: 4px 0; text-align: right;">{{ $proprietaire->fullName() }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Numéro</td><td style="padding: 4px 0; text-align: right;">{{ $proprietaire->phone }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Adresse</td><td style="padding: 4px 0; text-align: right;">{{ $proprietaire->address ?? '—' }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Contact urgence</td><td style="padding: 4px 0; text-align: right;">{{ $proprietaire->emergency_contact ?? '—' }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Mairie de retrait</td><td style="padding: 4px 0; text-align: right;">{{ $demande->mairie->name }}</td></tr>
            <tr><td style="padding: 4px 0; color: #b9a87a;">Date de retrait</td><td style="padding: 4px 0; text-align: right;">{{ $demande->retrait_date?->format('d/m/Y') ?? date('d/m/Y') }}</td></tr>
        </table>
    </div>
</div>
