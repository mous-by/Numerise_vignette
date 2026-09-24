{{-- Modèle « rose » (App\Enums\VgtCardTemplate) — rose et bleu, vignette 2023 (commune). Reproduit la mise en page des vraies vignettes motos maliennes (photos fournies par le développeur) sur une carte CR80 85,6 × 54 mm ; verso : nom et numéro du détenteur seulement. PROPOSITION TECHNIQUE (CLAUDE.md §5). --}}
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
  .vc-r,.vc-v{background:linear-gradient(120deg,#f6a6cb 0%,#f08bbd 45%,#d966a9 100%);color:#1a2a63}
  .yr{left:4.6mm;top:5.6mm;width:6.4mm;font-size:16pt;line-height:1.12;text-align:center;color:#1f3f9a}
  .sn{left:1.8mm;top:47mm;transform:rotate(-90deg);transform-origin:0 0;white-space:nowrap;font-size:6.6pt;letter-spacing:1.5pt;color:#1a2a63}
  .h1{left:27mm;width:38mm;top:3mm;font-size:5.8pt;font-weight:700;color:#1a2a63}
  .h2{left:27mm;width:38mm;top:6.2mm;font-size:5pt;font-style:italic;color:#1a2a63}
  .h3{left:27mm;width:38mm;top:9.4mm;font-size:6.4pt;font-weight:900;line-height:1.15;color:#101a46}
  .h4{left:24mm;width:46mm;top:14.6mm;font-size:20pt;letter-spacing:.9pt;color:#e0ad35;line-height:1;text-shadow:.15mm .15mm 0 rgba(90,40,10,.35)}
  .pill{left:45mm;top:22.6mm;min-width:27mm;box-sizing:border-box;text-align:center;padding:.8mm 2mm;background:#fff;border:.4mm solid #1a2a63;border-radius:2.4mm;font-size:9pt;color:#1a2a63}
  .nb{left:26.5mm;top:31.5mm;width:56mm;height:8.4mm;box-sizing:border-box;border:.3mm solid #1f3f9a;border-radius:2mm;background:#fff;display:flex;align-items:center;padding:0 2mm;gap:2mm}
  .nb b{font-size:8pt} .nb i{font-style:normal;margin-left:auto;font-size:11pt;font-weight:800;letter-spacing:1.2pt}
  .bd{left:0;right:0;top:41.6mm;height:5.2mm;background:#1b1b1b;color:#fff;text-align:center;font-size:5.7pt;font-weight:700;line-height:5.2mm}
  .nt{left:3mm;right:3mm;top:47.6mm;text-align:center;font-size:4.5pt;line-height:1.34;font-weight:600;color:#fff}
  .gc{left:82.4mm;top:12mm;writing-mode:vertical-rl;font-size:4pt;letter-spacing:.5pt;color:#1a2a63}
  .p-arms{left:9.6mm;top:2.6mm;width:14.4mm;height:17.8mm}
  .p-seal{left:70.4mm;top:2.8mm;width:11mm;height:11mm}
  .p-scooter{left:8.4mm;top:26.4mm;width:16.4mm;height:11.5mm}
</style>
<div class="vc vc-r" data-model="rose">
  <svg class="e" style="left:0;top:0;width:85.6mm;height:54mm" viewBox="0 0 856 540" preserveAspectRatio="none"><defs><pattern id="gw" width="90" height="34" patternUnits="userSpaceOnUse" patternTransform="rotate(-24)"><path d="M0 17 Q22 0 45 17 T90 17" fill="none" stroke="#3a5bc4" stroke-opacity=".5" stroke-width="1.1"/><path d="M0 24 Q22 7 45 24 T90 24" fill="none" stroke="#3a5bc4" stroke-opacity=".32" stroke-width=".8"/></pattern><pattern id="gw2" width="90" height="34" patternUnits="userSpaceOnUse" patternTransform="rotate(28)"><path d="M0 17 Q22 30 45 17 T90 17" fill="none" stroke="#ffffff" stroke-opacity=".35" stroke-width="1"/></pattern></defs><rect width="856" height="540" fill="url(#gw)"/><rect width="856" height="540" fill="url(#gw2)"/></svg>
  <div class="e yr hv">@foreach ($digits as $digit)<div>{{ $digit }}</div>@endforeach</div>
  <div class="e sn">N° {{ $number }}</div>
  @include('demandes-vgt._card_parts.arms', ['class' => 'e p-arms', 'variant' => '2023'])
  @include('demandes-vgt._card_parts.seal', ['class' => 'e p-seal', 'fill' => '#2b6cc4', 'stroke' => '#fff', 'accent' => '#f2c230'])
  <div class="e h1 ctr">REPUBLIQUE DU MALI</div>
  <div class="e h2 ctr">Un peuple - Un but - Une foi</div>
  <div class="e h3 ctr">{{ $issuer }}</div>
  <div class="e h4 hv ctr">VIGNETTE</div>
  <div class="e pill hv">{{ $amount }}</div>
  @include('demandes-vgt._card_parts.scooter', ['class' => 'e p-scooter', 'color' => '#1f3f9a', 'hub' => '#f08bbd'])
  <div class="e nb"><b>N°</b><i>{{ $moto->plate_number }}</i></div>
  <div class="e bd">ENGINS A 2 ROUES — {{ $genre }}</div>
  <div class="e nt">Les auteurs ou complices de falsification ou de contrefaçon de vignettes seront<br>punis conformément aux lois et actes en vigueur.<br>Souscrivez pour le développement de votre commune</div>
</div>
<div class="vc vc-v" data-model="rose">
  <div class="vb">
    <div class="vl">Nom</div><div class="vv">{{ $proprietaire->fullName() }}</div>
    <div class="vl">Numéro</div><div class="vv">{{ $proprietaire->phone }}</div>
  </div>
</div>
