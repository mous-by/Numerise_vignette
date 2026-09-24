{{-- Modèle « Moderne » (App\Enums\VgtCardTemplate::Moderne) — dégradé bleu-orange, typographie large. --}}
<div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 380px; margin: 24px auto; border-radius: 16px; overflow: hidden; box-shadow: 0 6px 18px rgba(0,0,0,0.15);">
    <div style="background: linear-gradient(135deg, #1d4e89 0%, #f97316 100%); color: #fff; padding: 20px;">
        <div style="font-size: 22px; font-weight: 800; letter-spacing: 0.5px;">VigiMoto</div>
        <div style="font-size: 13px; opacity: 0.9; margin-top: 2px;">Vignette VGT {{ $demande->vgt_year }}</div>
        <div style="font-size: 26px; font-weight: 800; margin-top: 10px; letter-spacing: 1px;">{{ $demande->moto->plate_number }}</div>
    </div>
    <div style="padding: 18px 20px; background: #fff; font-size: 13px; color: #222;">
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;"><span style="color:#888;">Propriétaire</span><strong>{{ $proprietaire->fullName() }}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;"><span style="color:#888;">Numéro</span><strong>{{ $proprietaire->phone }}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;"><span style="color:#888;">Adresse</span><strong>{{ $proprietaire->address ?? '—' }}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;"><span style="color:#888;">Contact urgence</span><strong>{{ $proprietaire->emergency_contact ?? '—' }}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;"><span style="color:#888;">Mairie</span><strong>{{ $demande->mairie->name }}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0;"><span style="color:#888;">Retrait</span><strong>{{ $demande->retrait_date?->format('d/m/Y') ?? date('d/m/Y') }}</strong></div>
    </div>
</div>
