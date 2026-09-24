{{-- Modèle « classique » (App\Enums\VgtCardTemplate) — blanc et rouge, vignette 2018 du District de Bamako. Reproduit la mise en page des vraies vignettes motos maliennes (photos fournies par le développeur) sur une carte CR80 85,6 × 54 mm ; verso : nom et numéro du détenteur seulement. PROPOSITION TECHNIQUE (CLAUDE.md §5). --}}
@php
    $moto = $demande->moto;
    $serial = 'VGT-'.$demande->vgt_year.'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT);
    $number = str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT);
    $amount = number_format($demande->totalAmount(), 0, ',', ' ').' F CFA';
    $issuer = mb_strtoupper($demande->mairie->name);
    $genre = mb_strtoupper($moto->type_or_brand);
    $digits = str_split((string) $demande->vgt_year);
    $qrText = app(\App\Services\Vgt\VignetteVerifier::class)->url($demande);
@endphp
<style>
  .vc{width:85.6mm;height:54mm;box-sizing:border-box;position:relative;overflow:hidden;page-break-after:always;break-after:page;font-family:Arial,Helvetica,sans-serif;line-height:1.2;border-radius:3mm}
  .vc:last-child{page-break-after:auto;break-after:auto}
  .e{position:absolute} .e svg,svg.e{display:block}
  .hv{font-family:'Archivo Black','Arial Black',Impact,Arial,sans-serif;font-weight:400}
  .ctr{text-align:center}
  .qr{background:#fff;padding:.7mm;box-sizing:border-box;line-height:0} .qr svg{width:100%;height:100%;display:block}
  .vb{position:absolute;left:9mm;top:19mm;right:9mm}
  .vl{font-size:5.4pt;text-transform:uppercase;letter-spacing:.8pt;opacity:.65;margin-top:3.5mm} .vv{font-size:10.5pt;font-weight:700;margin-top:.6mm;border-bottom:.2mm solid currentColor;padding-bottom:1mm}
  .vc-r,.vc-v{background:#fbfbfa;color:#1a1a1a;border:.2mm solid #d6d6d2}
  .yr{left:2.4mm;top:5mm;width:6.4mm;font-size:18pt;line-height:1.06;text-align:center;color:#1a1a1a}
  .h1{left:24mm;width:40mm;top:3.4mm;font-size:5pt;color:#333}
  .h2{left:24mm;width:40mm;top:6mm;font-size:4.6pt;color:#333}
  .h2 span{border-bottom:.22mm solid #333;padding:0 1mm .3mm}
  .h3{left:24mm;width:40mm;top:9.4mm;font-size:6.3pt;font-weight:900;color:#111}
  .h4{left:24mm;width:40mm;top:12.6mm;font-size:16pt;letter-spacing:1.2pt;color:#d9261c;line-height:1.05}
  .pill{left:26mm;top:22.2mm;width:27mm;box-sizing:border-box;text-align:center;padding:.7mm 1mm;background:#fff;border:.3mm solid #222;border-radius:2mm;font-size:7.4pt;color:#111}
  .vn{left:57mm;top:24mm;font-size:7.6pt;font-weight:900;letter-spacing:.4pt}
  .nl{left:26mm;top:35.8mm;font-size:4.1pt;font-weight:700;line-height:1.1}
  .nb{left:35.6mm;top:31.4mm;width:46mm;height:9.2mm;box-sizing:border-box;border:.3mm solid #222;border-radius:2.6mm;background:#fff;display:flex;align-items:center;justify-content:center}
  .nb i{font-style:normal;font-size:11.8pt;font-weight:800;letter-spacing:1.1pt}
  .bd{left:0;right:0;top:46.6mm;height:5.6mm;background:#1b1b1b;color:#fff;text-align:center;font-size:5.6pt;font-weight:700;line-height:5.6mm}
  .sm{left:1.2mm;top:53mm;transform-origin:0 0;font-size:3pt;color:#777}
  .p-arms{left:9.6mm;top:2.6mm;width:13.4mm;height:16.6mm}
  .p-signature{left:62mm;top:12.2mm;width:16mm;height:8mm}
  .p-holo{left:68.6mm;top:2.6mm;width:11mm;height:11mm}
  .p-scooter{left:8.6mm;top:28mm;width:17mm;height:11.9mm}
</style>
<div class="vc vc-r" data-model="classique">
  <div class="e yr hv">@foreach ($digits as $digit)<div>{{ $digit }}</div>@endforeach</div>
  @include('demandes-vgt._card_parts.arms', ['class' => 'e p-arms', 'variant' => '2018'])
  <div class="e h1 ctr">REPUBLIQUE DU MALI</div>
  <div class="e h2 ctr"><span>Un Peuple - Un But - Une Foi</span></div>
  <div class="e h3 ctr">{{ $issuer }}</div>
  <div class="e h4 hv ctr">VIGNETTE</div>
  <div class="e pill hv">{{ $amount }}</div>
  <div class="e vn">N°{{ $number }}</div>
  @include('demandes-vgt._card_parts.signature', ['class' => 'e p-signature', 'color' => '#2a3a8c'])
  @include('demandes-vgt._card_parts.holo', ['class' => 'e p-holo', 'uid' => 'c'])
  @include('demandes-vgt._card_parts.scooter', ['class' => 'e p-scooter', 'photo' => true])
  <div class="e nl">N°<br>MATRICULE</div>
  <div class="e nb"><i>{{ $moto->plate_number }}</i></div>
  <div class="e bd">ENGINS A 2 ROUES — {{ $genre }}</div>
</div>
<div class="vc vc-v" data-model="classique">
  <div class="vb">
    <div class="vl">Nom</div><div class="vv">{{ $proprietaire->fullName() }}</div>
    <div class="vl">Numéro</div><div class="vv">{{ $proprietaire->phone }}</div>
  </div>
</div>
