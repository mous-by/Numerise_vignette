{{-- Modèle « Minimaliste » (App\Enums\VgtCardTemplate::Minimaliste) — blanc, fines lignes, sans couleur. --}}
<div style="font-family: 'Courier New', monospace; max-width: 380px; margin: 24px auto; background: #fff; color: #111; border: 1px solid #ccc; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid #111; padding-bottom: 8px; margin-bottom: 14px;">
        <span style="font-size: 13px; letter-spacing: 2px; text-transform: uppercase;">VigiMoto</span>
        <span style="font-size: 11px; color: #555;">VGT {{ $demande->vgt_year }}</span>
    </div>
    <div style="font-size: 20px; letter-spacing: 1px; margin-bottom: 14px;">{{ $demande->moto->plate_number }}</div>
    <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
        <tr><td style="padding: 3px 0; color: #666; width: 45%;">Proprietaire</td><td style="padding: 3px 0;">{{ $proprietaire->fullName() }}</td></tr>
        <tr><td style="padding: 3px 0; color: #666;">Numero</td><td style="padding: 3px 0;">{{ $proprietaire->phone }}</td></tr>
        <tr><td style="padding: 3px 0; color: #666;">Adresse</td><td style="padding: 3px 0;">{{ $proprietaire->address ?? '-' }}</td></tr>
        <tr><td style="padding: 3px 0; color: #666;">Urgence</td><td style="padding: 3px 0;">{{ $proprietaire->emergency_contact ?? '-' }}</td></tr>
        <tr><td style="padding: 3px 0; color: #666;">Mairie</td><td style="padding: 3px 0;">{{ $demande->mairie->name }}</td></tr>
        <tr><td style="padding: 3px 0; color: #666;">Retrait</td><td style="padding: 3px 0;">{{ $demande->retrait_date?->format('d/m/Y') ?? date('d/m/Y') }}</td></tr>
    </table>
</div>
