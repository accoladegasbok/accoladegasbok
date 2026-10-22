{{-- FILE: resources/views/partials/_home-trust.blade.php
     Trust strip under the finder bar on the home page. Warranty wording comes from App\Support\Warranty. --}}
@php [$__wn, $__wu] = \App\Support\Warranty::badge(); @endphp
<style>
  .ts{ padding:34px 24px 6px; }
  .ts-grid{ max-width:1080px; margin:0 auto; display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
  .ts-item{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-left:3px solid var(--gold); background:rgba(255,255,255,.55); border-radius:0 10px 10px 0; }
  .ts-big{ font-family:'Big Shoulders Display',sans-serif; font-weight:800; font-size:28px; line-height:1; color:var(--navy-deep); min-width:58px; }
  .ts-small{ font-size:12.5px; line-height:1.35; color:var(--steel); font-weight:500; }
  @media (max-width:920px){ .ts-grid{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width:520px){ .ts-big{ font-size:23px; min-width:48px; } }
</style>
<div class="ts">
  <div class="ts-grid">
    <div class="ts-item"><div class="ts-big">3</div><div class="ts-small">countries served: Nigeria, Ghana and the USA</div></div>
    <div class="ts-item"><div class="ts-big">A·B·C</div><div class="ts-small">honest condition grade on every stock tag</div></div>
    <div class="ts-item"><div class="ts-big">{{ $__wn }}</div><div class="ts-small">{{ strtolower($__wu) }} of warranty, from your invoice date</div></div>
    <div class="ts-item"><div class="ts-big">VIN</div><div class="ts-small">donor vehicle and history kept on file for each part</div></div>
  </div>
</div>
