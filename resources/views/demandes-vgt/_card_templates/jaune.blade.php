{{-- Modèle « jaune » (App\Enums\VgtCardTemplate) — jaune, vignette 2025 du District de Bamako. Reproduit la mise en page des vraies vignettes motos maliennes (photos fournies par le développeur) sur une carte CR80 85,6 × 54 mm ; verso : nom et numéro du détenteur seulement. PROPOSITION TECHNIQUE (CLAUDE.md §5). --}}
@php
    $moto = $demande->moto;
    $serial = 'VGT-'.$demande->vgt_year.'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT);
    $number = str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT);
    $amount = number_format($demande->totalAmount(), 0, ',', ' ').' F CFA';
    $issuer = mb_strtoupper($demande->mairie->name);
    $genre = mb_strtoupper($moto->type_or_brand);
    $digits = str_split((string) $demande->vgt_year);
    $qrText = $serial.' '.$moto->plate_number;
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
  .vc-r,.vc-v{background:linear-gradient(135deg,#fbe36a 0%,#f7cb2f 55%,#efb50c 100%);color:#1c2450}
  .yr{left:4.8mm;top:6.6mm;width:6.4mm;font-size:15.5pt;line-height:1.08;text-align:center;color:#1c2450}
  .sn{left:1.8mm;top:39.6mm;transform:rotate(-90deg);transform-origin:0 0;white-space:nowrap;font-size:6.4pt;letter-spacing:1.4pt;color:#3a3a2a}
  .h1{left:29mm;width:47mm;top:3mm;font-size:5.7pt;font-weight:700;color:#3a3320}
  .h2{left:29mm;width:47mm;top:6.3mm;font-size:4.8pt;font-style:italic;color:#3a3320}
  .h2 span{border-bottom:.22mm solid #3a3320;padding:0 1mm .3mm}
  .h3{left:29mm;width:47mm;top:9.6mm;font-size:6.6pt;font-weight:900;color:#1c2450}
  .h4{left:29mm;width:41mm;top:12.8mm;font-size:19pt;letter-spacing:.6pt;color:#26262a;line-height:1}
  .pill{left:33.5mm;top:21.6mm;min-width:24mm;box-sizing:border-box;text-align:center;padding:.7mm 2mm;background:#1c2450;border-radius:1.4mm;font-size:8.4pt;color:#ffe47a}
  .nb{left:29.5mm;top:31mm;width:52mm;height:8mm;box-sizing:border-box;border:.3mm solid #1c2450;border-radius:2.4mm;background:#fffbe8;display:flex;align-items:center;padding:0 2.5mm;gap:2mm}
  .nb b{font-size:9pt} .nb i{font-style:normal;margin-left:auto;font-size:11.5pt;font-weight:800;letter-spacing:1.2pt}
  .bd{left:0;right:0;top:41.3mm;height:5.2mm;background:#1b1b1b;color:#fff;text-align:center;font-size:5.7pt;font-weight:700;line-height:5.2mm}
  .nt{left:3mm;right:3mm;top:47.4mm;text-align:center;font-size:4.3pt;line-height:1.32;font-weight:600;color:#1c2450}
  .gc{left:82.4mm;top:22mm;writing-mode:vertical-rl;font-size:4.2pt;letter-spacing:.6pt;color:#5a4a20}
  .sl{width:6.4mm;height:6.4mm;border-radius:50%;border:.3mm solid #b58a10;background:radial-gradient(circle,#f8e6a0,#d9a520)}
  .p-arms{left:10.8mm;top:2.4mm;width:16.8mm;height:20.8mm}
  .p-scooter{left:14.6mm;top:32.4mm;width:14mm;height:9.8mm}
  .p-signature{left:58.6mm;top:20.4mm;width:10mm;height:6.4mm}
  .p-tower{left:68.4mm;top:9mm;width:14.2mm;height:21.3mm}
</style>
<div class="vc vc-r" data-model="jaune">
  <svg class="e" style="left:0;top:0;width:85.6mm;height:54mm" viewBox="0 0 856 540" preserveAspectRatio="none"><defs><pattern id="gw" width="70" height="26" patternUnits="userSpaceOnUse" patternTransform="rotate(-14)"><path d="M0 13 Q17 0 35 13 T70 13" fill="none" stroke="#ffffff" stroke-opacity=".38" stroke-width="1.2"/><path d="M0 20 Q17 7 35 20 T70 20" fill="none" stroke="#c99a10" stroke-opacity=".28" stroke-width=".8"/></pattern></defs><rect width="856" height="540" fill="url(#gw)"/></svg>
  <div class="e yr hv">@foreach ($digits as $digit)<div>{{ $digit }}</div>@endforeach</div>
  <div class="e sn">{{ $number }}</div>
  @include('demandes-vgt._card_parts.arms', ['class' => 'e p-arms'])
  <div class="e qr" data-qr="{{ $qrText }}" style="left:12.8mm;top:23.6mm;width:8.8mm;height:8.8mm"></div>
  @include('demandes-vgt._card_parts.scooter', ['class' => 'e p-scooter', 'color' => '#c2305c', 'hub' => '#fbe36a'])
  <div class="e sl" style="left:70mm;top:2.4mm"></div><div class="e sl" style="left:77mm;top:2.4mm"></div>
  <div class="e h1 ctr">REPUBLIQUE DU MALI</div>
  <div class="e h2 ctr"><span>Un Peuple - Un But - Une Foi</span></div>
  <div class="e h3 ctr">{{ $issuer }}</div>
  <div class="e h4 hv ctr">VIGNETTE</div>
  <div class="e pill hv">{{ $amount }}</div>
  @include('demandes-vgt._card_parts.signature', ['class' => 'e p-signature', 'color' => '#1d2f6f'])
  @include('demandes-vgt._card_parts.tower', ['class' => 'e p-tower', 'uid' => 'j', 'c1' => '#d98a9d', 'c2' => '#fbd9cf', 'c3' => '#9c3652'])
  <div class="e gc">GCMS</div>
  <div class="e nb"><b>N°</b><i>{{ $moto->plate_number }}</i></div>
  <div class="e bd">ENGINS A 2 ROUES — {{ $genre }}</div>
  <div class="e nt">Les auteurs ou complices de falsification ou de contrefaçon de vignettes seront<br>punis conformément aux lois et actes en vigueur.</div>
</div>
<div class="vc vc-v" data-model="jaune">
  <div class="vb">
    <div class="vl">Nom</div><div class="vv">{{ $proprietaire->fullName() }}</div>
    <div class="vl">Numéro</div><div class="vv">{{ $proprietaire->phone }}</div>
  </div>
</div>
