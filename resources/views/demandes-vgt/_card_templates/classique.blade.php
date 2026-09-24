{{-- Modèle « Classique » (App\Enums\VgtCardTemplate::Classique) — identité VigiMoto sobre, bleu et blanc. --}}
<div style="font-family: Arial, sans-serif; max-width: 380px; margin: 24px auto; border: 2px solid #1d4e89; border-radius: 10px; overflow: hidden;">
    <div style="background: #1d4e89; color: #fff; padding: 12px 16px;">
        <div style="font-size: 18px; font-weight: 700;">VIGI<span style="color:#f97316;">MOTO</span></div>
        <div style="font-size: 12px;">Carte de vignette VGT — {{ $demande->vgt_year }}</div>
    </div>
    <div style="padding: 16px; font-size: 14px; color: #123a63;">
        <p style="margin: 4px 0;"><strong>Matricule :</strong> {{ $demande->moto->plate_number }}</p>
        <p style="margin: 4px 0;"><strong>Propriétaire :</strong> {{ $proprietaire->fullName() }}</p>
        <p style="margin: 4px 0;"><strong>Numéro :</strong> {{ $proprietaire->phone }}</p>
        <p style="margin: 4px 0;"><strong>Adresse :</strong> {{ $proprietaire->address ?? '—' }}</p>
        <p style="margin: 4px 0;"><strong>Contact urgence :</strong> {{ $proprietaire->emergency_contact ?? '—' }}</p>
        <p style="margin: 4px 0;"><strong>Mairie de retrait :</strong> {{ $demande->mairie->name }}</p>
        <p style="margin: 4px 0;"><strong>Date de retrait :</strong> {{ $demande->retrait_date?->format('d/m/Y') ?? date('d/m/Y') }}</p>
    </div>
</div>
