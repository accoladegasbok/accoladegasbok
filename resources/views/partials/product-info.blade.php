{{--
  FILE: resources/views/partials/product-info.blade.php

  AllStarJDM-style Product Information block.
  Include on any part detail page:
    @include('partials.product-info', ['part' => $part])

  Auto-populates from part data.
  Admin can override any field using the product_info JSON column.
--}}

@php
  // Parse stored product info (admin overrides)
  $stored = [];
  if (!empty($part->product_info)) {
      $stored = json_decode($part->product_info, true) ?? [];
  }

  // ── Auto-build fitment string ─────────────────────────────────
  $compatFrom = $part->compat_year_from ?? $part->year_from;
  $compatTo   = $part->compat_year_to   ?? $part->year_to;
  $yearRange  = ($compatFrom == $compatTo)
      ? $compatFrom
      : "{$compatFrom}-{$compatTo}";

  $fitment = $stored['fitment']
      ?? "{$yearRange} {$part->brand} {$part->model}"
         . ($part->compatible_trims ? " ({$part->compatible_trims})" : '');

  // ── Auto-build type string ────────────────────────────────────
  $typeLabel = $stored['type'] ?? null;
  if (!$typeLabel) {
      if ($part->part_category === 'Transmission') {
          $typeLabel = 'Automatic Transmission';
          if ($part->transmission_code_oem ?? null) {
              $typeLabel .= " · {$part->transmission_code_oem}";
          }
          if ($part->pin_count ?? null) {
              $typeLabel .= " · {$part->pin_count}-pin";
          }
      } elseif ($part->part_category === 'Engine') {
          $typeLabel = 'Complete Engine';
          if ($part->engine_code_oem ?? null) {
              $typeLabel .= " · {$part->engine_code_oem}";
          }
      } else {
          $typeLabel = ucfirst($part->part_category) . ' Part';
          if ($part->side && $part->side !== 'N/A') {
              $typeLabel .= " · {$part->side}";
          }
      }
  }

  // ── Origin ────────────────────────────────────────────────────
  $originMap = [
      'JDM'          => 'JDM (Japanese Domestic Market)',
      'USDM'         => 'USDM (US Domestic Market)',
      'EDM'          => 'EDM (European Domestic Market)',
      'Nigerian Used' => 'Nigerian Used',
      'N/A'          => null,
  ];
  $originLabel = $stored['origin']
      ?? ($originMap[$part->origin_market ?? 'N/A'] ?? null)
      ?? ($part->origin && $part->origin !== 'N/A' ? $part->origin : null);

  // ── Included ──────────────────────────────────────────────────
  $included = $stored['included'] ?? match($part->part_category) {
      'Transmission' => 'Complete Transmission (as pictured)',
      'Engine'       => 'Complete Engine Assembly (as pictured)',
      'Body'         => ucfirst($part->part_name) . ' (as pictured)',
      'Suspension'   => ucfirst($part->part_name) . ' Assembly',
      default        => ucfirst($part->part_name) . ' (as pictured)',
  };

  // ── Warranty ──────────────────────────────────────────────────
  $warranty = $stored['warranty'] ?? match($part->part_category) {
      'Engine', 'Transmission' => '90 Days',
      'Electrical'             => '30 Days',
      'Airbag'                 => 'No warranty — safety critical part',
      default                  => '30 Days',
  };

  // ── Grade label ───────────────────────────────────────────────
  $gradeLabel = [
      'A'   => 'Grade A — Like new, low mileage',
      'B'   => 'Grade B — Good condition, minor wear',
      'C'   => 'Grade C — Functional, may have cosmetic wear',
      'New' => 'New OEM part',
  ][$part->condition_grade] ?? 'Grade B';

  // ── Notes ─────────────────────────────────────────────────────
  $notes = $stored['notes'] ?? match($part->part_category) {
      'Transmission' => 'Reuse your original sensors and components for proper installation.',
      'Engine'       => 'Reuse your original accessories (alternator, AC compressor, intake, etc.) for proper installation.',
      'Airbag'       => 'SAFETY CRITICAL: Must match exact position, year, model, seat type, and origin. Never interchange across brands or generations.',
      'Body'         => ($part->colour ? "Colour: {$part->colour}. " : '') . 'Painting or colour matching may be required.',
      default        => null,
  };

  // Custom extra bullets from stored JSON
  $extras = $stored['extras'] ?? [];
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">

  {{-- Header --}}
  <h2 class="font-display font-700 text-navy text-lg tracking-wide mb-1 uppercase">Product Information</h2>
  <p class="text-sm text-gray-600 font-body font-500 mb-5">
    {{ $yearRange }} {{ strtoupper($part->brand) }} {{ strtoupper($part->model) }}
    {{ strtoupper($part->part_name) }}
    @if($part->transmission_code_oem ?? null) {{ $part->transmission_code_oem }} @endif
    @if($part->engine_code_oem ?? null) {{ $part->engine_code_oem }} @endif
  </p>

  {{-- Bullet list --}}
  <ul class="space-y-3">

    {{-- Fitment --}}
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Fitment:</strong> <span class="text-gray-700">{{ $fitment }}</span></span>
    </li>

    {{-- Type --}}
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Type:</strong> <span class="text-gray-700">{{ $typeLabel }}</span></span>
    </li>

    {{-- Grade / Condition --}}
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Condition:</strong> <span class="text-gray-700">{{ $gradeLabel }}</span></span>
    </li>

    {{-- Mileage --}}
    @if($part->mileage)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Mileage:</strong> <span class="text-gray-700">{{ number_format($part->mileage) }} miles at time of harvest</span></span>
    </li>
    @endif

    {{-- Origin --}}
    @if($originLabel)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Origin:</strong> <span class="text-gray-700">{{ $originLabel }}</span></span>
    </li>
    @endif

    {{-- Pin count (Transmission/Gear — Nigerian market) --}}
    @if(($part->pin_count ?? null) && $part->part_category === 'Transmission')
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Pin Count:</strong>
        <span class="text-gray-700">{{ $part->pin_count }} pins
          @if($part->gear_alias ?? null) · {{ $part->gear_alias }}@endif
        </span>
      </span>
    </li>
    @endif

    {{-- Not compatible note --}}
    @if($part->not_compatible_note ?? null)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-red-600 font-500">Not compatible with:</strong> <span class="text-gray-700">{{ $part->not_compatible_note }}</span></span>
    </li>
    @endif

    {{-- Warranty --}}
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Warranty:</strong> <span class="text-gray-700">{{ $warranty }}</span></span>
    </li>

    {{-- Included --}}
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Included:</strong> <span class="text-gray-700">{{ $included }}</span></span>
    </li>

    {{-- Any custom extra bullets admin added --}}
    @foreach($extras as $extra)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">{{ $extra['label'] }}:</strong> <span class="text-gray-700">{{ $extra['value'] }}</span></span>
    </li>
    @endforeach

    {{-- Notes --}}
    @if($notes)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gold flex-shrink-0 mt-1.5"></span>
      <span><strong class="text-navy font-500">Notes:</strong> <span class="text-gray-700">{{ $notes }}</span></span>
    </li>
    @endif

    {{-- Donor VIN (optional — builds trust) --}}
    @if($part->donor_vin)
    <li class="flex items-start gap-3 text-sm font-body">
      <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0 mt-1.5"></span>
      <span class="text-gray-400 text-xs font-mono">Donor VIN: {{ $part->donor_vin }}</span>
    </li>
    @endif

  </ul>
</div>
