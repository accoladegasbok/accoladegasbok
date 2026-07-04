{{-- FILE: resources/views/parts/show.blade.php --}}
@extends('layouts.app')
@section('title', $part->part_name . ' — ' . $part->brand . ' ' . $part->model . ' — Auto Zenith Parts')

@push('head')
<meta property="og:title" content="{{ $part->part_name }} — {{ $part->brand }} {{ $part->model }}">
@if(!empty($photos[0]))<meta property="og:image" content="{{ $photos[0] }}">@endif
<style>
.thumb-btn.active{border-color:#C8960C}
.spec-row:nth-child(even){background:#f9f9f9}
.grade-a{background:#EAF3DE;color:#27500A;border:1px solid #C0DD97}
.grade-b{background:#E6F1FB;color:#0C447C;border:1px solid #B5D4F4}
.grade-c{background:#FAEEDA;color:#633806;border:1px solid #FAC775}
.grade-new{background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC}
.fitment-box{background:#0A1F5C;color:#fff;border-radius:14px;padding:1.25rem}
.fitment-year{background:#C8960C;color:#0A1F5C;font-weight:700;padding:2px 10px;border-radius:20px;font-size:12px}
.fitment-trim{background:rgba(255,255,255,.1);border-radius:6px;padding:2px 8px;font-size:11px;display:inline-block;margin:2px}
.not-compat{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;border-radius:10px;padding:.65rem 1rem;font-size:12px}
@keyframes added{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}
.btn-added{animation:added .3s ease}
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 text-xs font-body text-gray-400 flex items-center gap-1.5 flex-wrap">
  <a href="{{ route('parts.search') }}" class="hover:text-navy">Parts</a>
  <span>/</span>
  <a href="{{ route('parts.search', ['make'=>$part->brand]) }}" class="hover:text-navy">{{ $part->brand }}</a>
  <span>/</span>
  <span class="text-navy font-500">{{ $part->part_name }}</span>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">

    {{-- ── Photos ──────────────────────────────────────────────── --}}
    <div>
      <div class="bg-gray-100 rounded-2xl overflow-hidden aspect-[4/3] mb-3 relative">
        @if(!empty($photos[0]))
          <img id="mainPhoto" src="{{ $photos[0] }}" alt="{{ $part->part_name }}"
            class="w-full h-full {{ $hasRealPhotos ? 'object-cover' : 'object-contain p-6' }} cursor-zoom-in" onclick="openLightbox(0)">
        @else
          <div class="w-full h-full flex items-center justify-center text-gray-300 text-sm font-body">No photo yet</div>
        @endif
        @if($part->status !== 'Available')
          <div class="absolute top-3 left-3 bg-red-600 text-white text-xs font-display font-700 px-3 py-1.5 rounded-full">{{ strtoupper($part->status) }}</div>
        @endif
        @if($hasRealPhotos && count($photos) > 1)
          <div class="absolute bottom-3 right-3 bg-black bg-opacity-60 text-white text-xs font-body px-2.5 py-1 rounded-full">📷 {{ count($photos) }}</div>
        @endif
      </div>
      @if($hasRealPhotos && count($photos) > 1)
      <div class="grid grid-cols-6 gap-2">
        @foreach($photos as $i => $url)
          <button onclick="setPhoto({{ $i }},'{{ $url }}')"
            class="thumb-btn aspect-square rounded-xl overflow-hidden border-2 {{ $i===0?'border-gold active':'border-transparent' }} transition-all hover:border-gold">
            <img src="{{ $url }}" class="w-full h-full object-cover" loading="lazy">
          </button>
        @endforeach
      </div>
      @endif
      @if(!empty($part->video_path))
      <div class="mt-3">
        <video controls class="w-full rounded-2xl border border-gray-200">
          <source src="{{ asset(config('media.prefix') . '/' . $part->video_path) }}">
        </video>
      </div>
      @endif
      <div class="flex items-center justify-between mt-3 text-xs font-body text-gray-400">
        <span>Code: <span class="font-mono font-500 text-gray-600">{{ $part->part_code }}</span></span>
        <button onclick="shareUrl()" class="hover:text-navy transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
          Share
        </button>
      </div>
    </div>

    {{-- ── Part Info + Price + Cart ─────────────────────────────── --}}
    <div>
      <div class="text-xs text-gray-400 font-body mb-2">{{ $part->brand }} · {{ $part->model }} · {{ $part->part_category }}</div>

      <h1 class="font-display font-700 text-navy text-3xl leading-tight tracking-wide mb-1">
        {{ $part->part_name }}
        @if($part->side && $part->side !== 'N/A')
          <span class="text-gray-400 font-600 text-xl">· {{ $part->side }}</span>
        @endif
      </h1>

      {{-- OEM codes — item 6 & 7 --}}
      @if(($part->engine_code_oem ?? null) || ($part->transmission_code_oem ?? null))
      <div class="flex flex-wrap gap-2 mb-3">
        @if($part->engine_code_oem ?? null)
          <span class="inline-flex items-center gap-1.5 bg-navy text-gold font-mono font-700 text-sm px-3 py-1 rounded-lg">
            Engine: {{ $part->engine_code_oem }}
          </span>
        @endif
        @if($part->transmission_code_oem ?? null)
          <span class="inline-flex items-center gap-1.5 bg-navy text-gold font-mono font-700 text-sm px-3 py-1 rounded-lg">
            Gear: {{ $part->transmission_code_oem }}
            @if($part->pin_count ?? null)
              · {{ $part->pin_count }}-pin
            @endif
          </span>
        @endif
        @if($part->pin_count ?? null)
          <span class="inline-flex items-center gap-1.5 bg-navy text-gold font-mono font-700 text-sm px-3 py-1 rounded-lg">
            {{ $part->pin_count }}-pin connector
          </span>
        @endif
        @if($part->drive_type ?? null)
          <span class="inline-flex items-center gap-1.5 bg-blue-600 text-white font-display font-700 text-sm px-3 py-1 rounded-lg">
            {{ $part->drive_type }}
          </span>
        @elseif($part->gear_alias ?? null)
          @php
              $driveFromAlias = null;
              foreach (['4WD','4x4','AWD','2WD','4x2','FWD','RWD'] as $dt) {
                  if (str_contains(strtoupper($part->gear_alias), str_replace('x','X',$dt))) { $driveFromAlias = $dt; break; }
              }
          @endphp
          @if($driveFromAlias)
          <span class="inline-flex items-center gap-1.5 bg-blue-600 text-white font-display font-700 text-sm px-3 py-1 rounded-lg">
            {{ $driveFromAlias }}
          </span>
          @endif
        @endif
        @if($part->gear_alias ?? null)
          <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200 font-body font-500 text-xs px-3 py-1 rounded-lg">
            {{ $part->gear_alias }}
          </span>
        @endif
        @if(($part->origin_market ?? 'N/A') !== 'N/A')
          <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 border border-blue-200 font-body font-500 text-xs px-3 py-1 rounded-lg">
            {{ $part->origin_market }}
          </span>
        @endif
      </div>
      @endif

      {{-- Grade + mileage --}}
      <div class="flex flex-wrap gap-2 mb-5">
        <span class="inline-flex items-center text-sm font-body font-500 px-3 py-1.5 rounded-full
          {{ $part->condition_grade==='A'?'grade-a':($part->condition_grade==='B'?'grade-b':($part->condition_grade==='New'?'grade-new':'grade-c')) }}">
          @if($part->condition_grade==='A') ★★★ Grade A — Like New
          @elseif($part->condition_grade==='B') ★★ Grade B — Good
          @elseif($part->condition_grade==='New') ✦ New OEM
          @else ★ Grade C — Functional @endif
        </span>
        @if($part->mileage)
          <span class="text-sm font-body text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full">🔢 {{ number_format($part->mileage) }} mi</span>
        @endif
      </div>

      {{-- Price block — fixed, in this part's own currency. No live FX,
           no currency switcher: every part has exactly one real price. --}}
      <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 mb-5">
        <div class="flex items-end justify-between gap-4">
          <div>
            <div class="text-xs text-gray-400 font-body uppercase tracking-wider mb-1">Price</div>
            <div class="font-display font-800 text-navy text-4xl leading-none">{{ $priceDisplay }}</div>
          </div>
        </div>
        <div class="flex items-center gap-2 mt-3 text-sm font-body">
          @if($part->status === 'Available')
            <span class="w-2.5 h-2.5 bg-green-500 rounded-full"></span>
            <span class="text-green-700 font-500">In stock</span>
            <span class="text-gray-400">· {{ $part->location }}</span>
          @else
            <span class="w-2.5 h-2.5 bg-red-500 rounded-full"></span>
            <span class="text-red-600 font-500">{{ $part->status }}</span>
          @endif
        </div>
      </div>

      {{-- Add to cart + WhatsApp --}}
      @if($part->status === 'Available')
      <div class="grid grid-cols-2 gap-3 mb-4">
        <button id="addToCartBtn" onclick="addToCart({{ $part->id }}, this)"
          class="flex items-center justify-center gap-2 font-display font-700 text-sm py-4 rounded-xl tracking-wide transition-all
            {{ $inCart ? 'bg-green-500 text-white' : 'bg-gold hover:bg-yellow-500 text-navy' }}">
          @if($inCart)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            IN CART
          @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            ADD TO CART
          @endif
        </button>
        <a href="https://wa.me/{{ $waNumber }}?text={{ $waMsg }}" target="_blank"
          class="flex items-center justify-center gap-2 font-display font-700 text-sm py-4 rounded-xl tracking-wide bg-green-500 hover:bg-green-600 text-white transition-colors">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
          ENQUIRE
        </a>
      </div>
      @if($inCart)
        <a href="{{ route('cart.index') }}" class="block w-full text-center font-body font-500 text-sm text-navy border border-navy py-3 rounded-xl hover:bg-navy hover:text-white transition-colors mb-4">
          View Cart & Checkout →
        </a>
      @endif
      @else
        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Hi, can you source a '.$part->part_name.' for a '.$yearRange.' '.$part->brand.' '.$part->model.'?') }}"
          target="_blank"
          class="flex items-center justify-center gap-2 font-display font-700 text-sm py-4 rounded-xl bg-green-500 hover:bg-green-600 text-white transition-colors mb-4 w-full">
          Ask us to source this part
        </a>
      @endif

      {{-- Specs table --}}
      <div class="border border-gray-200 rounded-2xl overflow-hidden text-sm font-body">
        @foreach([
          ['OEM Part Number', $part->oem_part_number],
          ['Engine Code',     $part->engine_code_oem ?? null],
          ['Gear / Trans Code',$part->transmission_code_oem ?? null],
          ['Pin Count',       ($part->pin_count ?? null) ? ($part->pin_count . ' pins') : null],
          ['Drive Type',      $part->drive_type ?? null],
          ['Gear Alias',      $part->gear_alias ?? null],
          ['Origin Market',   ($part->origin_market ?? 'N/A') !== 'N/A' ? ($part->origin_market ?? null) : null],
          ['Side',            $part->side !== 'N/A' ? $part->side : null],
          ['Body Style',      $part->body_style],
          ['Location',        $part->location],
        ] as [$label, $value])
          @if($value)
          <div class="spec-row flex gap-4 px-4 py-2.5 border-b border-gray-100 last:border-0">
            <span class="text-gray-400 font-500 w-36 flex-shrink-0">{{ $label }}</span>
            <span class="text-navy font-500 font-mono text-xs">{{ $value }}</span>
          </div>
          @endif
        @endforeach
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════════
       VEHICLE FITMENT PANEL — AllStarJDM style (items 4, 5, 9, 11)
  ══════════════════════════════════════════════════════════════ --}}
  <div class="mb-10">
    <h2 class="font-display font-700 text-navy text-xl tracking-wide mb-4 uppercase flex items-center gap-2">
      <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Vehicle Fitment
    </h2>

    <div class="fitment-box mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Confirmed fit --}}
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider mb-2 font-body">Direct Fit For</div>
          <div class="font-display font-700 text-white text-lg">{{ $part->brand }} {{ $part->model }}</div>
          <div class="flex flex-wrap gap-1 mt-2">
            @php
              $yFrom = $part->compat_year_from ?? $part->year_from;
              $yTo   = $part->compat_year_to   ?? $part->year_to;
            @endphp
            @for($y = $yFrom; $y <= $yTo; $y++)
              <span class="fitment-year">{{ $y }}</span>
            @endfor
          </div>
        </div>

        {{-- Compatible trims --}}
        @if($part->compatible_trims ?? null)
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider mb-2 font-body">Compatible Trims</div>
          <div class="flex flex-wrap gap-1">
            @foreach(explode(',', $part->compatible_trims) as $trim)
              <span class="fitment-trim">{{ trim($trim) }}</span>
            @endforeach
          </div>
        </div>
        @endif

        {{-- Engine / gear --}}
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider mb-2 font-body">Engine / Gear</div>
          @if($part->engine_code_oem ?? null)
            <div class="font-mono font-700 text-gold text-sm">{{ $part->engine_code_oem }}</div>
          @endif
          @if($part->transmission_code_oem ?? null)
            <div class="font-mono font-700 text-gold text-sm">{{ $part->transmission_code_oem }}
              @if($part->pin_count ?? null) · {{ $part->pin_count }}-pin @endif
            </div>
          @endif
          @if(!($part->engine_code_oem ?? null) && !($part->transmission_code_oem ?? null))
            <div class="text-gray-400 text-sm font-body">See fitment notes</div>
          @endif
        </div>
      </div>
    </div>

    {{-- Full fitment notes --}}
    @if($part->fitment_notes ?? null)
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-4 text-sm font-body text-blue-900 leading-relaxed">
      <div class="font-500 text-blue-700 uppercase tracking-wider text-xs mb-2">Fitment Notes</div>
      {{ $part->fitment_notes }}
    </div>
    @endif

    {{-- Not compatible note --}}
    @if($part->not_compatible_note ?? null)
    <div class="not-compat mb-4">
      <strong>⚠ Not compatible:</strong> {{ $part->not_compatible_note }}
    </div>
    @endif

    {{-- Also Fits from parts_compatibility table --}}
    @if(!empty($alsoFits))
    <div class="bg-green-50 border border-green-100 rounded-2xl p-5 mb-4">
      <div class="text-xs font-body font-500 text-green-700 uppercase tracking-wider mb-3">
        Platform-compatible vehicles — same part may fit:
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($alsoFits as $af)
        <div class="bg-white border border-green-100 rounded-xl p-3.5">
          <div class="font-display font-700 text-navy text-sm">{{ $af['brand'] ?? '' }} {{ $af['model'] ?? '' }}</div>
          @if(!empty($af['year_from']))
            <div class="text-xs text-gray-500 font-body mt-0.5">{{ $af['year_from'] }}–{{ $af['year_to'] }}</div>
          @endif
          @if(!empty($af['notes']))
            <div class="text-xs text-green-600 font-body mt-1 leading-relaxed">{{ $af['notes'] }}</div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- ── Interchange group — confirmed compatible vehicles + combined
         stock count. Requires $interchangeVehicles / $aggregatedStock
         to be passed from the controller (see InterchangeService). ── --}}
    @if(!empty($interchangeVehicles) && count($interchangeVehicles) > 0)
    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-4">
      <div class="flex items-center justify-between mb-3">
        <div class="text-xs font-body font-500 text-blue-700 uppercase tracking-wider">
          Interchangeable — also fits these vehicles
        </div>
        @if(isset($aggregatedStock))
        <div class="text-sm font-display font-700 text-navy">{{ $aggregatedStock }} total in stock</div>
        @endif
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($interchangeVehicles as $iv)
        <div class="bg-white border border-blue-100 rounded-xl p-3.5">
          <div class="font-display font-700 text-navy text-sm">{{ $iv->make }} {{ $iv->model }}</div>
          <div class="text-xs text-gray-500 font-body mt-0.5">{{ $iv->year_from }}@if($iv->year_to != $iv->year_from)–{{ $iv->year_to }}@endif</div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Airbag safety --}}
    @if($part->part_category === 'Airbag')
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
      <div class="text-xs font-body font-500 text-red-600 uppercase tracking-wider mb-2">⚠ Safety Critical — Airbag</div>
      <p class="text-sm font-body text-red-700 leading-relaxed">
        Airbags must exactly match: year, brand, model, position ({{ $part->airbag_position ?? 'see listing' }}),
        seat material, body style, and origin. Never interchange airbags across brands or generations.
        Incorrect airbag may fail to deploy or cause injury.
      </p>
    </div>
    @endif
  </div>

  {{-- Donor vehicle --}}
  @if($donor)
  <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-10">
    <h2 class="font-display font-700 text-navy text-lg tracking-wide mb-4 uppercase">Donor Vehicle</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm font-body">
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">VIN</div><div class="font-mono text-navy text-xs">{{ $donor->vin }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Vehicle</div><div class="font-500 text-navy">{{ $donor->year }} {{ $donor->make }} {{ $donor->model }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Mileage at harvest</div><div class="font-500 text-navy">{{ number_format($donor->mileage) }} mi</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Source</div><div class="font-500 text-navy">{{ $donor->source ?? '—' }}</div></div>
    </div>
  </div>
  @endif

  {{-- Related parts --}}
  @if($related->isNotEmpty())
  <div>
    <h2 class="font-display font-700 text-navy text-xl tracking-wide mb-5 uppercase">More {{ $part->brand }} {{ $part->part_category }} Parts</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      @foreach($related as $rel)
      @php $rYears = $rel->year_from===$rel->year_to ? $rel->year_from : "{$rel->year_from}–{$rel->year_to}"; @endphp
      <a href="{{ route('parts.show', $rel->id) }}"
        class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md hover:-translate-y-1 transition-all group">
        <div class="aspect-square bg-gray-100 overflow-hidden">
          @if($rel->thumb)
            <img src="{{ $rel->thumb }}" class="w-full h-full {{ isset($rel->has_real_photo) && !$rel->has_real_photo ? 'object-contain p-3' : 'object-cover group-hover:scale-105' }} transition-transform duration-300" loading="lazy">
          @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
          @endif
        </div>
        <div class="p-3">
          <div class="text-xs text-gray-400 font-body mb-0.5">{{ $rYears }}</div>
          <div class="font-display font-700 text-navy text-sm leading-tight mb-1.5 line-clamp-2">{{ $rel->part_name }}</div>
          <div class="font-display font-700 text-navy text-sm">{{ $rel->price_display }}</div>
        </div>
      </a>
      @endforeach
    </div>
  </div>
  @endif

</div>

{{-- Lightbox --}}
@if($hasRealPhotos && count($photos) > 1)
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center" onclick="closeLightbox()">
  <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white text-3xl">×</button>
  <button onclick="prevPhoto(event)" class="absolute left-4 text-white text-3xl px-4 py-2">‹</button>
  <img id="lightboxImg" src="" class="max-w-4xl max-h-screen object-contain" onclick="event.stopPropagation()">
  <button onclick="nextPhoto(event)" class="absolute right-4 text-white text-3xl px-4 py-2">›</button>
</div>
@endif

@endsection

@push('scripts')
<script>
const photos = @json($photos);
let activeIndex = 0;

function setPhoto(i, url) {
  activeIndex = i;
  document.getElementById('mainPhoto')?.setAttribute('src', url);
  document.querySelectorAll('.thumb-btn').forEach((b,idx) => {
    b.classList.toggle('active', idx===i);
    b.classList.toggle('border-gold', idx===i);
    b.classList.toggle('border-transparent', idx!==i);
  });
}
function openLightbox(i) {
  if (!photos.length) return;
  activeIndex = i;
  document.getElementById('lightboxImg').src = photos[i];
  document.getElementById('lightbox').classList.remove('hidden');
  document.getElementById('lightbox').classList.add('flex');
}
function closeLightbox() {
  document.getElementById('lightbox').classList.add('hidden');
  document.getElementById('lightbox').classList.remove('flex');
}
function prevPhoto(e) { e.stopPropagation(); activeIndex=(activeIndex-1+photos.length)%photos.length; openLightbox(activeIndex); }
function nextPhoto(e) { e.stopPropagation(); activeIndex=(activeIndex+1)%photos.length; openLightbox(activeIndex); }
document.addEventListener('keydown', e => {
  if (e.key==='Escape') closeLightbox();
  if (e.key==='ArrowLeft') prevPhoto(e);
  if (e.key==='ArrowRight') nextPhoto(e);
});

async function addToCart(partId, btn) {
  if (btn.classList.contains('bg-green-500')) { window.location.href='/cart'; return; }
  btn.disabled = true;
  const orig = btn.innerHTML;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
  try {
    const res = await fetch('/cart/add', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
      body:JSON.stringify({part_id:partId}),
    });
    const data = await res.json();
    if (data.success) {
      btn.disabled = false;
      btn.className = btn.className.replace('bg-gold hover:bg-yellow-500 text-navy','bg-green-500 text-white btn-added');
      btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> IN CART';
      btn.onclick = () => window.location.href='/cart';
      document.querySelectorAll('.cart-badge').forEach(b => { b.textContent=data.count; b.classList.remove('hidden'); });
    } else { btn.disabled=false; btn.innerHTML=orig; alert(data.error||'Could not add to cart.'); }
  } catch(e) { btn.disabled=false; btn.innerHTML=orig; }
}

async function shareUrl() {
  if (navigator.share) { navigator.share({title:'{{ $part->part_name }} — Auto Zenith Parts', url:window.location.href}); }
  else { await navigator.clipboard.writeText(window.location.href); alert('Link copied!'); }
}

window.AZ_PAGE_CONTEXT = {
  part_name: "{{ $part->part_name }}",
  vehicle:   "{{ $yearRange }} {{ $part->brand }} {{ $part->model }}",
  price:     "{{ $priceDisplay }}",
};
</script>
@endpush
