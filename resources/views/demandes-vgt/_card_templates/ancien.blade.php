{{-- Modèle « ancien » (App\Enums\VgtCardTemplate) — blanc à lignes ondulées, vignette 2017 du District de Bamako. Reproduit la mise en page des vraies vignettes motos maliennes (photos fournies par le développeur) sur une carte CR80 85,6 × 54 mm ; verso : nom et numéro du détenteur seulement. PROPOSITION TECHNIQUE (CLAUDE.md §5). --}}
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
  .vc-r,.vc-v{background:#f6f9fc;color:#1a1a1a}
  .h1{left:26mm;width:52mm;top:5mm;font-size:6.2pt;font-weight:900;letter-spacing:.3pt;color:#222}
  .h2{left:26mm;width:52mm;top:8mm;font-size:5pt;font-weight:700;color:#222}
  .h3{left:26mm;width:52mm;top:11.2mm;font-size:6pt;font-weight:900;color:#222}
  .h4{left:20mm;width:62mm;top:16.4mm;font-size:16pt;letter-spacing:4.2pt;color:#8c2a52;line-height:1;font-weight:900}
  .h5{left:20mm;width:62mm;top:23.6mm;font-size:14pt;letter-spacing:5pt;color:#5b8fbd;line-height:1;font-weight:700}
  .h6{left:20mm;width:62mm;top:30.4mm;font-size:11.5pt;letter-spacing:1.2pt;color:#b0892a;line-height:1;font-weight:900}
  .vn{left:3mm;top:45mm;transform:rotate(-90deg);transform-origin:0 0;white-space:nowrap;font-size:6.4pt;font-weight:800;letter-spacing:.8pt}
  .nl{left:28mm;top:38.2mm;font-size:5.6pt;font-weight:700}
  .nb{left:42mm;top:36.4mm;width:40mm;height:7.2mm;box-sizing:border-box;border:.3mm solid #444;border-radius:2mm;background:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center}
  .nb i{font-style:normal;font-size:10.5pt;font-weight:800;letter-spacing:1pt}
  .nt{left:6mm;right:6mm;top:46mm;font-size:4.4pt;line-height:1.35;font-weight:600;color:#222}
  .mt{left:6mm;top:44.4mm;font-size:4.4pt;color:#222}
  .p-signature{left:8mm;top:2.4mm;width:16mm;height:8mm}
  .p-arms{left:5.6mm;top:12.4mm;width:13.4mm;height:16.6mm}
  .p-scooter{left:5mm;top:30mm;width:19mm;height:13.3mm}
</style>
<div class="vc vc-r" data-model="ancien">
  <svg class="e" style="left:0;top:0;width:85.6mm;height:54mm" viewBox="0 0 856 540" preserveAspectRatio="none"><defs><pattern id="gw" width="60" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate(-8)"><path d="M0 11 Q15 0 30 11 T60 11" fill="none" stroke="#b7cbe0" stroke-width="1.6"/><path d="M0 17 Q15 6 30 17 T60 17" fill="none" stroke="#cbdaea" stroke-width="1.2"/></pattern></defs><rect width="856" height="540" fill="url(#gw)"/></svg>
  @include('demandes-vgt._card_parts.signature', ['class' => 'e p-signature', 'color' => '#222'])
  @include('demandes-vgt._card_parts.arms', ['class' => 'e p-arms'])
  <div class="e h1 ctr">REPUBLIQUE DU MALI</div>
  <div class="e h2 ctr">Un peuple - Un but - Une foi</div>
  <div class="e h3 ctr">{{ $issuer }}</div>
  <div class="e h4 ctr">VIGNETTE</div>
  <div class="e h5 ctr">{{ implode(' ', $digits) }}</div>
  <div class="e h6 ctr">{{ str_replace(' ', '', number_format($demande->totalAmount(), 0, ',', '')) }} F CFA</div>
  <div class="e vn">N° {{ $number }}</div>
  @include('demandes-vgt._card_parts.scooter', ['class' => 'e p-scooter', 'color' => '#22272b', 'hub' => '#f6f9fc'])
  <div class="e nl">MATRICULE</div>
  <div class="e nb"><i>{{ $moto->plate_number }}</i></div>
  <div class="e nt">ENGINS A 2 ROUES — {{ $genre }}<br>Les auteurs ou complices de falsification ou de contrefaçon de vignettes seront punis conformément aux lois et actes en vigueur.</div>
</div>
<div class="vc vc-v" data-model="ancien">
  <div class="vb">
    <div class="vl">Nom</div><div class="vv">{{ $proprietaire->fullName() }}</div>
    <div class="vl">Numéro</div><div class="vv">{{ $proprietaire->phone }}</div>
  </div>
</div>
