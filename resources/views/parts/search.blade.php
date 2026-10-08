@extends('layouts.app')
@php use Illuminate\Support\Arr; @endphp

@section('title', 'Search Auto Parts — Auto Zenith Parts')
@section('meta_desc', 'Search quality used auto parts by VIN or vehicle make and model. Toyota, Lexus, Honda, Nissan and more. USA, Nigeria and Ghana locations.')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════════
     HERO — VIN Search Bar (LKQ-style hero with prominent VIN input)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-navy relative overflow-hidden">
    {{-- Subtle grid texture --}}
    <div class="absolute inset-0 opacity-10" style="background-image:linear-gradient(rgba(255,255,255,.15) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.15) 1px,transparent 1px);background-size:32px 32px;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="text-center mb-7">
            <h1 class="font-display font-800 text-white text-4xl sm:text-5xl tracking-wide leading-tight">
                FIND THE RIGHT PART,<br><span class="text-gold">EVERY TIME</span>
            </h1>
            <p class="text-gray-300 font-body text-sm mt-2">Search by VIN for exact-fit results, or browse by Make and Model</p>
        </div>

        {{-- ── VIN Search Box ───────────────────────────────────────────── --}}
        <div class="max-w-3xl mx-auto mb-6">
            <div class="bg-white rounded-xl p-1 flex gap-1 shadow-2xl">
                <div class="flex-1 relative">
                    <input type="text" id="vinInput" maxlength="17" placeholder="Enter 17-digit VIN (e.g. 1HGBH41JXMN109186)"
                        class="vin-input w-full px-4 py-3.5 rounded-lg text-sm font-body font-500 border border-gray-200 focus:outline-none uppercase tracking-wider placeholder:normal-case placeholder:tracking-normal placeholder:text-gray-400 placeholder:font-300">
                    <div id="vinCharCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">0/17</div>
                </div>
                <button id="vinDecodeBtn" class="bg-gold hover:bg-gold-dark text-navy font-display font-700 text-sm px-6 py-3.5 rounded-lg transition-colors tracking-wide whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    DECODE VIN
                </button>
            </div>
            <p class="text-center text-gray-400 text-xs mt-2 font-body">
                VIN found on dashboard, driver door jamb, or vehicle title · Powered by NHTSA (free)
            </p>
        </div>

        {{-- ── Trust / Activity Stats — real numbers, builds confidence ──── --}}
        <div class="max-w-3xl mx-auto mb-2 grid grid-cols-3 gap-3 text-center">
            <div class="bg-white bg-opacity-10 rounded-xl px-3 py-3">
                <div class="font-display font-800 text-gold text-2xl">{{ number_format($totalAvailable) }}+</div>
                <div class="text-xs text-gray-300 font-body mt-0.5">Parts In Stock Now</div>
            </div>
            <div class="bg-white bg-opacity-10 rounded-xl px-3 py-3">
                <div class="font-display font-800 text-gold text-2xl">{{ number_format($totalOrdersEver) }}+</div>
                <div class="text-xs text-gray-300 font-body mt-0.5">Orders Fulfilled</div>
            </div>
            <div class="bg-white bg-opacity-10 rounded-xl px-3 py-3">
                <div class="font-display font-800 text-gold text-2xl">📈</div>
                <div class="text-xs text-gray-300 font-body mt-0.5">Growing Every Day</div>
            </div>
        </div>

        {{-- ── Special Order Notice — for parts not currently in stock ──── --}}
        <div class="max-w-3xl mx-auto mb-6">
            <div class="bg-gold bg-opacity-10 border border-gold border-opacity-40 rounded-2xl px-5 py-4 flex flex-col sm:flex-row items-center gap-3 text-center sm:text-left">
                <div class="text-2xl">📦</div>
                <div class="flex-1">
                    <div class="font-display font-700 text-white text-sm">Can't find your part?</div>
                    <div class="text-xs text-gray-300 font-body mt-0.5">We can special-order it from the USA — typical delivery to Nigeria or Ghana is 30–60 days. Send us a message and we'll confirm availability and pricing.</div>
                </div>
                <button type="button" onclick="openWaPicker()" class="bg-gold text-navy font-display font-700 text-xs px-5 py-2.5 rounded-xl hover:bg-yellow-400 transition-colors whitespace-nowrap">
                    Send Us a Message
                </button>
            </div>
        </div>


<div id="vinResultBanner" class="max-w-3xl mx-auto hidden">
  <div class="border border-white border-opacity-20 rounded-2xl overflow-hidden">

    {{-- Vehicle identity row --}}
    <div class="bg-white bg-opacity-10 px-5 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 bg-gold rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <div>
          <div id="vinVehicleLabel" class="text-white font-display font-700 text-lg tracking-wide"></div>
          <div id="vinDetailsLabel" class="text-gray-300 text-xs font-body mt-0.5"></div>
        </div>
      </div>
      <button id="vinClearBtn" class="text-gray-400 hover:text-white text-xs font-body underline flex-shrink-0">Clear</button>
    </div>

      </div>
</div>


        {{-- ── Error banner --}}
        <div id="vinErrorBanner" class="max-w-3xl mx-auto hidden">
            <div class="bg-red-900 bg-opacity-60 border border-red-500 rounded-xl px-5 py-3 text-red-200 text-sm text-center font-body" id="vinErrorText"></div>
        </div>

        {{-- ── Tab switcher: VIN vs Browse ─────────────────────────────────── --}}
        <div class="flex justify-center gap-2 mt-5">
            <button id="tabVin" class="tab-btn bg-gold text-navy font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide">VIN SEARCH</button>
            <button id="tabBrowse" class="tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors">BROWSE BY VEHICLE</button>
            <a href="{{ route('other-items') }}?category=Consumable" class="tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors inline-block">SHOP OILS & FLUIDS</a>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     BROWSE DROPDOWNS (hidden by default, shown when Browse tab active)
═══════════════════════════════════════════════════════════════════════════ --}}
@php
    // What is already chosen (so the chips are rebuilt after a search / page change)
    $initVehicles = array_map(fn($v) => $v['year'] . '|' . $v['make'] . '|' . $v['model'], $filters['vehicles'] ?? []);
    $initParts    = $filters['parts'] ?? [];
@endphp
<div id="browsePanel" class="bg-white border-b border-gray-200 shadow-sm hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <form method="GET" action="{{ route('parts.search') }}" id="browseForm">

            {{-- Row 1 — pick a vehicle, then ADD VEHICLE (up to 10 vehicles in one search) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                {{-- Make --}}
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Make</label>
                    <select name="make" id="makeSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">All Makes</option>
                        @foreach($makes as $brand)
                            <option value="{{ $brand }}" {{ ($filters['make'] ?? '') === $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model (populated via AJAX) --}}
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Model</label>
                   <select name="model" id="modelSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">All Models</option>
                        @if(!empty($filters['model']))
                        <option value="{{ $filters['model'] }}" selected>{{ $filters['model'] }}</option>
                        @endif
                   </select>
                </div>

                {{-- Year --}}
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Year</label>
                    <select name="year" id="yearSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">All Years</option>
                        @for($y = date('Y'); $y >= 1995; $y--)
                            <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Part Category --}}
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Part Type</label>
                    <select name="category" id="categorySelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">All Types</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat === 'Consumable' ? 'Oils, Fluids & Filters' : $cat }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Add vehicle --}}
                <div class="flex items-end">
                    <button type="button" id="addVehicleBtn" class="w-full border-2 border-navy text-navy hover:bg-navy hover:text-white font-display font-700 text-sm px-4 py-2.5 rounded-lg tracking-wide transition-colors">
                        + ADD VEHICLE
                    </button>
                </div>
            </div>

            {{-- Chosen vehicles --}}
            <div id="vehicleChips" class="flex flex-wrap gap-2 mt-3"></div>

            {{-- Row 2 — type part names (suggestions show how many are in stock), then search --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mt-3 items-end">
                <div class="md:col-span-3 relative">
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Parts <span class="normal-case text-gray-400">(up to 10)</span></label>
                    <input type="text" id="partInput" autocomplete="off" placeholder="Hood, fender, alternator, transmission..."
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                    <div id="partSuggest" class="hidden absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto"></div>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-navy hover:bg-navy-light text-white font-display font-700 text-sm px-4 py-2.5 rounded-lg tracking-wide transition-colors">
                        SEARCH PARTS
                    </button>
                    {{-- Reset: back to an empty search (clears vehicles, parts, category and every filter) --}}
                    <a href="{{ route('parts.search') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 text-gray-600 hover:border-navy hover:text-navy text-sm font-display font-700 tracking-wide transition-colors">
                        RESET
                    </a>
                </div>
            </div>

            {{-- Chosen parts --}}
            <div id="partChips" class="flex flex-wrap gap-2 mt-3"></div>

            {{-- The chips travel with the search as v[] and part[] --}}
            <div id="chipInputs"></div>

            <p class="text-[11px] text-gray-400 font-body mt-3">Add up to 10 vehicles and 10 parts, then search once. Pick a make, model and year, press ADD VEHICLE, and repeat for the next one.</p>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     RESULTS AREA — Sidebar + Grid (LKQ layout)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ══════════════════════════════════════════════════════════════════
             LEFT SIDEBAR — Filters (XL Parts / LKQ style)
        ══════════════════════════════════════════════════════════════════ --}}
        <aside class="w-full lg:w-64 flex-shrink-0">
            <form method="GET" action="{{ route('parts.search') }}" id="filterForm">

                {{-- Hidden fields to preserve VIN / browse selections --}}
                <input type="hidden" name="make"     value="{{ $filters['make'] }}">
                <input type="hidden" name="model"    value="{{ $filters['model'] }}">
                <input type="hidden" name="year"     value="{{ $filters['year'] }}">
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
                @foreach($filters['vehicles'] ?? [] as $vv)
                <input type="hidden" name="v[]" value="{{ $vv['year'] }}|{{ $vv['make'] }}|{{ $vv['model'] }}">
                @endforeach
                @foreach($filters['parts'] ?? [] as $pp)
                <input type="hidden" name="part[]" value="{{ $pp }}">
                @endforeach
                

                {{-- Keyword search --}}
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4 shadow-sm">
                    <label class="block text-xs font-body font-500 text-gray-500 mb-2 uppercase tracking-wider">Search by Keyword</label>
                    <div class="relative">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' ?? '' ?? '' ?? '' ?? '' ?? '' }}" placeholder="Part name, OEM number..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 pr-8 text-sm font-body focus:outline-none focus:border-gold">
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Filter card --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-navy px-4 py-3">
                        <h2 class="font-display font-700 text-white text-sm tracking-wide uppercase">Filter Results</h2>
                    </div>

                    {{-- Location --}}
                    <div class="p-4 border-b border-gray-100">
                        <button type="button" class="filter-toggle w-full flex justify-between items-center text-left mb-3" data-target="loc-filter">
                            <span class="font-body font-500 text-sm text-gray-700 uppercase tracking-wider">Location</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="loc-filter" class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="location" value="" {{ empty($filters['location']) ? 'checked' : '' }}
                                    class="accent-gold" onchange="this.form.submit()">
                                <span class="text-sm font-body text-gray-600 group-hover:text-navy">All Locations <span class="text-xs text-gray-400">({{ number_format(($yardCounts['USA'] ?? 0) + ($yardCounts['Lagos'] ?? 0)) }})</span></span>
                            </label>
                            {{-- Lagos hub: "Lagos" = all West Africa stock; the others are the yards (shown to customers as Lagos / Ibadan ...) --}}
                            @foreach($locations as $locValue => $locLabel)
                            <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="location" value="{{ $locValue }}" {{ ($filters['location'] ?? '') === $locValue ? 'checked' : '' }}
                            class="accent-gold" onchange="this.form.submit()">
                            <span class="text-sm font-body {{ ($yardCounts[$locValue] ?? 0) > 0 ? 'text-gray-600' : 'text-gray-300' }} group-hover:text-navy">{{ $locLabel }} <span class="text-xs text-gray-400">({{ number_format($yardCounts[$locValue] ?? 0) }})</span></span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Trim — shown once a vehicle is chosen, with how many parts fit each trim --}}
                    @if(!empty($trimOptions))
                    <div class="p-4 border-b border-gray-100">
                        <button type="button" class="filter-toggle w-full flex justify-between items-center text-left mb-3" data-target="trim-filter">
                            <span class="font-body font-500 text-sm text-gray-700 uppercase tracking-wider">Trim</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="trim-filter" class="space-y-2 max-h-56 overflow-y-auto">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="trim" value="" {{ empty($filters['trim']) ? 'checked' : '' }} class="accent-gold" onchange="this.form.submit()">
                                <span class="text-sm font-body text-gray-600 group-hover:text-navy">All trims</span>
                            </label>
                            @foreach($trimOptions as $opt)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="trim" value="{{ $opt['label'] }}" {{ strcasecmp($filters['trim'] ?? '', $opt['label']) === 0 ? 'checked' : '' }} class="accent-gold" onchange="this.form.submit()">
                                <span class="text-sm font-body text-gray-600 group-hover:text-navy">{{ $opt['label'] }} <span class="text-xs text-gray-400">({{ $opt['n'] }})</span></span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Condition Grade --}}
                    <div class="p-4 border-b border-gray-100">
                        <button type="button" class="filter-toggle w-full flex justify-between items-center text-left mb-3" data-target="cond-filter">
                            <span class="font-body font-500 text-sm text-gray-700 uppercase tracking-wider">Condition Grade</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="cond-filter" class="space-y-2">
                            @foreach([''=>'All Conditions','A'=>'Grade A — Like New / Low Mileage','B'=>'Grade B — Good, Minor Wear','C'=>'Grade C — Functional, Cosmetic Damage','New'=>'New OEM Part'] as $val => $lbl)
                                <label class="flex items-start gap-2 cursor-pointer group">
                                    <input type="radio" name="condition" value="{{ $val }}" {{ ($filters['condition'] ?? '') === $val ? 'checked' : '' }}
                                        class="accent-gold mt-0.5 flex-shrink-0" onchange="this.form.submit()">
                                    <span class="text-sm font-body text-gray-600 group-hover:text-navy leading-tight">{{ $lbl }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Price Range — note: prices are in each part's own
                         fixed local currency now; filtering mixes currencies
                         if "All Locations" is selected. Best paired with a
                         Location filter for a meaningful range. --}}
                    <div class="p-4 border-b border-gray-100">
                        <button type="button" class="filter-toggle w-full flex justify-between items-center text-left mb-3" data-target="price-filter">
                            <span class="font-body font-500 text-sm text-gray-700 uppercase tracking-wider">Price Range</span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="price-filter" class="flex gap-2 items-center">
                            <input type="number" name="price_min" value="{{ $filters['price_min'] }}" placeholder="Min" min="0"
                                class="w-1/2 border border-gray-200 rounded-lg px-2 py-2 text-sm font-body focus:outline-none focus:border-gold">
                            <span class="text-gray-400 text-sm">—</span>
                            <input type="number" name="price_max" value="{{ $filters['price_max'] }}" placeholder="Max" min="0"
                                class="w-1/2 border border-gray-200 rounded-lg px-2 py-2 text-sm font-body focus:outline-none focus:border-gold">
                        </div>
                        <p class="text-xs text-gray-400 font-body mt-2">Tip: select a Location above for an accurate range — each location prices in its own currency.</p>
                        <button type="submit" class="mt-2 w-full text-xs font-body font-500 text-gold hover:text-gold-dark underline text-left">Apply Price Filter</button>
                    </div>
                </div>

                {{-- Clear all filters --}}
                @if(array_filter(Arr::except($filters, ['sort'])))
                    <a href="{{ route('parts.search') }}" class="block text-center text-xs font-body text-red-600 hover:text-red-800 mt-3 underline">Clear all filters</a>
                @endif
            </form>
        </aside>

        {{-- ══════════════════════════════════════════════════════════════════
             MAIN CONTENT — Results header + Grid
        ══════════════════════════════════════════════════════════════════ --}}
        <main class="flex-1 min-w-0">

            {{-- Availability notice --}}
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-body px-4 py-2.5">
                Used parts inventory changes throughout the day. We confirm availability before finalising your order.
            </div>

            {{-- Results header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="font-display font-700 text-navy text-xl tracking-wide">
                        @if($total > 0)
                            {{ number_format($total) }} Parts Found
                        @else
                            No Parts Found
                        @endif
                    </h2>
                    @if(!empty($filters['make']) || !empty($filters['q'] ?? '' ?? '' ?? '' ?? '' ?? '' ?? ''))
                        <p class="text-sm text-gray-500 font-body mt-0.5">
                            Showing results for
                            @if(!empty($filters['make'])) <strong class="text-navy">{{ $filters['make'] }}</strong> @endif
                            @if(!empty($filters['model'])) <strong class="text-navy">{{ $filters['model'] }}</strong> @endif
                            @if(!empty($filters['year'])) <strong class="text-navy">{{ $filters['year'] }}</strong> @endif
                            @if(!empty($filters['q'] ?? '' ?? '' ?? '' ?? '' ?? '' ?? '')) "<strong class="text-navy">{{ $filters['q'] ?? '' ?? '' ?? '' ?? '' ?? '' ?? '' }}</strong>" @endif
                        </p>
                    @endif
                    @if(!empty($chips['vehicles']) || !empty($chips['parts']))
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        @foreach($chips['vehicles'] as $chip)
                        <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-1.5 bg-navy text-white text-xs font-body font-600 px-3 py-1 rounded-full hover:bg-navy-light" title="Remove this vehicle">
                            {{ $chip['label'] }} <span aria-hidden="true">&times;</span>
                        </a>
                        @endforeach
                        @foreach($chips['parts'] as $chip)
                        <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-1.5 bg-gold text-navy text-xs font-body font-600 px-3 py-1 rounded-full hover:bg-yellow-500" title="Remove this part">
                            {{ $chip['label'] }} <span aria-hidden="true">&times;</span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Sort + View toggle --}}
                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('parts.search') }}" id="sortForm">
                        @foreach(Arr::except($filters, ['sort', 'vehicles', 'parts']) as $k => $v)
                            @if($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        @foreach($filters['vehicles'] ?? [] as $vv)
                            <input type="hidden" name="v[]" value="{{ $vv['year'] }}|{{ $vv['make'] }}|{{ $vv['model'] }}">
                        @endforeach
                        @foreach($filters['parts'] ?? [] as $pp)
                            <input type="hidden" name="part[]" value="{{ $pp }}">
                        @endforeach
                        <select name="sort" onchange="this.form.submit()"
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold bg-white">
                            <option value="newest"     {{ ($filters['sort'] ?? 'newest') === 'newest'     ? 'selected' : '' }}>Newest First</option>
                            <option value="price_asc"  {{ ($filters['sort'] ?? '') === 'price_asc'        ? 'selected' : '' }}>Price: Low → High</option>
                            <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc'       ? 'selected' : '' }}>Price: High → Low</option>
                            <option value="mileage"    {{ ($filters['sort'] ?? '') === 'mileage'          ? 'selected' : '' }}>Lowest Mileage</option>
                        </select>
                    </form>

                    {{-- Grid / List toggle --}}
                    <div class="flex border border-gray-200 rounded-lg overflow-hidden">
                        <button id="gridViewBtn" class="view-toggle px-3 py-2 bg-navy text-white" title="Grid view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button id="listViewBtn" class="view-toggle px-3 py-2 bg-white text-gray-500 hover:bg-gray-50" title="List view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Active filter chips ──────────────────────────────────────── --}}
            @php
                $activeFilters = array_filter([
                    'make'      => $filters['make'],
                    'model'     => $filters['model'],
                    'year'      => $filters['year'],
                    'category'  => $categories[$filters['category']] ?? $filters['category'],
                    'location'  => $locations[$filters['location']] ?? $filters['location'],
                    'condition' => $filters['condition'],
                ]);
            @endphp
            @if($activeFilters)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($activeFilters as $key => $val)
                        @php
                            $removeUrl = request()->fullUrlWithQuery([$key => null]);
                        @endphp
                        <a href="{{ $removeUrl }}" class="inline-flex items-center gap-1.5 bg-navy text-white text-xs font-body font-500 px-3 py-1.5 rounded-full hover:bg-navy-dark transition-colors">
                            {{ $val }}
                            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- ── Parts Grid ───────────────────────────────────────────────── --}}
            @if($parts->isEmpty())
                {{-- Empty state --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center shadow-sm">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-display font-700 text-navy text-xl mb-2">No Parts Found</h3>
                    <p class="text-gray-500 font-body text-sm mb-6 max-w-sm mx-auto">We don't currently have this part in stock, but we can source it for you. Submit a special parts request.</p>
                    <button onclick="openWaPicker('{{ addslashes('Hi, I need a part that is not listed on your website. Vehicle: ' . ($filters['make'] ?? '') . ' ' . ($filters['model'] ?? '') . ' ' . ($filters['year'] ?? '') . '. Part needed: ' . ($filters['q'] ?? '')) }}')"
                       class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-body font-500 px-6 py-3 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                        Request This Part on WhatsApp
                    </button>
                </div>

                {{-- Recently sold (adverts) --}}
                @include('parts._sold-ads')

                {{-- The same request, saved to our staff inbox --}}
                @include('parts._request-form', [
                    'requestVehicle' => collect($chips['vehicles'] ?? [])->pluck('label')->implode(', ') ?: trim(($filters['year'] ?? '') . ' ' . ($filters['make'] ?? '') . ' ' . ($filters['model'] ?? '')),
                    'requestPart'    => collect($chips['parts'] ?? [])->pluck('label')->implode(', ') ?: ($filters['q'] ?? ''),
                    'requestSource'  => 'search',
                    'requestOpen'    => true,
                ])

            @else

                {{-- Grid view (default) --}}
                <div id="partsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($parts as $part)
                        @php
                            $photos     = json_decode($part->photos ?? '[]', true) ?: [];
                            $photoCount = count($photos);
                            $hasRealPhoto = $photoCount > 0;
                            $thumb      = !empty($photos[0])
                                ? asset(config('media.prefix') . '/' . $photos[0])
                                : asset('images/parts-photo-coming-soon.jpg');

                            // ── FIXED PRICE — each part shows its own real
                            // price in its own currency. No live FX math.
                            $priceLocal = $part->price_local ?? $part->price_usd; // fallback for pre-migration rows
                            $currencyCode = $part->currency_code ?? 'USD';
                            $currencySymbol = match($currencyCode) {
                                'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$',
                            };
                            $priceDecimals = $currencyCode === 'NGN' ? 0 : 2;
                            $price = $currencySymbol . number_format($priceLocal, $priceDecimals);

                            $locClass = match(true) {
                                str_contains($part->location, 'Nigeria') || str_contains($part->location, 'Lagos') => 'loc-ng',
                                str_contains($part->location, 'Ghana')   => 'loc-gh',
                                default => 'loc-us',
                            };
                            $locFlag = match(true) {
                                str_contains($part->location, 'Nigeria') || str_contains($part->location, 'Lagos') => '🇳🇬',
                                str_contains($part->location, 'Ghana')   => '🇬🇭',
                                default => '🇺🇸',
                            };
                            $gradeClass = match($part->condition_grade) {
                                'A'     => 'grade-a',
                                'B'     => 'grade-b',
                                'C'     => 'grade-c',
                                'New'   => 'grade-new',
                                default => 'grade-b',
                            };
                            $gradeLabel = match($part->condition_grade) {
                                'A'   => 'Grade A — Like New',
                                'B'   => 'Grade B — Good',
                                'C'   => 'Grade C — Fair',
                                'New' => 'New OEM',
                                default => 'Grade B',
                            };
                            $whatsappMsg = urlencode(
                                "Hi, I'm enquiring about the {$part->part_name} for a {$part->year_from}–{$part->year_to} {$part->brand} {$part->model}. " .
                                "Part ID: {$part->part_code}. Location: " . \App\Support\HubLocations::publicLabel($part->location) . ". Price: {$price}. Is this available?"
                            );
                        @endphp

                        <div class="part-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">

                            {{-- Photo --}}
                            <a href="{{ route('parts.show', $part->id) }}" class="block relative bg-gray-100 aspect-[4/3] overflow-hidden">
                                <img src="{{ $thumb ?: asset('images/coming-soon.jpg') }}" alt="{{ $part->part_name }}"
                                    class="w-full h-full {{ $hasRealPhoto ? 'object-cover hover:scale-105' : 'object-contain p-4' }} transition-transform duration-300"
                                    loading="lazy">

                                {{-- Photo count badge --}}
                                @if($photoCount > 1)
                                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-60 text-white text-xs font-body px-2 py-0.5 rounded-full">
                                        📷 {{ $photoCount }}
                                    </div>
                                @endif

                                {{-- Grade badge --}}
                                <div class="absolute top-2 left-2">
                                    <span class="inline-block text-xs font-body font-500 px-2.5 py-1 rounded-full {{ $gradeClass }}">
                                        {{ $gradeLabel }}
                                    </span>
                                </div>
                            </a>

                            {{-- Content --}}
                            <div class="p-4 flex flex-col flex-1">

                                {{-- Year / Make / Model — bold, prominent headline (matches industry standard layout) --}}
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="font-display font-700 text-navy text-sm tracking-wide">
                                        {{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif {{ $part->brand }} {{ $part->model }}
                                    </div>
                                    <span class="text-xs font-body font-500 px-2 py-0.5 rounded-full flex-shrink-0 {{ $locClass }}">
                                        {{ $locFlag }} {{ \App\Support\HubLocations::publicLabel($part->location) }}
                                    </span>
                                </div>

                                {{-- Part name --}}
                                <h3 class="font-body font-500 text-gray-600 text-sm leading-tight mb-1">
                                    <a href="{{ route('parts.show', $part->id) }}" class="hover:text-az-blue transition-colors">
                                        {{ $part->part_name }}
                                        @if($part->side && $part->side !== 'N/A')
                                            <span class="text-gray-400 font-600 text-sm">· {{ $part->side }}</span>
                                        @endif
                                    </a>
                                </h3>

                                {{-- Pin count + Drive type + Engine displacement/code — shown on
                                     transmissions and Complete Engine And Gear, key buying info
                                     for customers. drive_type/pin_count are real stored columns.
                                     Displacement is derived from the part's own engine_code_oem
                                     via OemDatabase (only shows for confirmed codes — no guessing). --}}
                                @php
                                    $displacementL = \App\Data\OemDatabase::displacementForCode($part->engine_code_oem ?? null);
                                @endphp
                                @if(($part->pin_count ?? null) || ($part->gear_alias ?? null) || ($part->drive_type ?? null) || $displacementL || ($part->engine_code_oem ?? null))
                                <div class="flex flex-wrap gap-1 mb-1">
                                    @if($displacementL)
                                    <span class="text-[10px] font-mono font-700 bg-gray-700 text-white px-1.5 py-0.5 rounded">{{ $displacementL }}L</span>
                                    @endif
                                    @if($part->engine_code_oem ?? null)
                                    <span class="text-[10px] font-mono font-700 bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $part->engine_code_oem }}</span>
                                    @endif
                                    @if($part->pin_count ?? null)
                                    <span class="text-[10px] font-mono font-700 bg-navy text-gold px-1.5 py-0.5 rounded">{{ $part->pin_count }}-pin</span>
                                    @endif
                                    @if($part->drive_type ?? null)
                                    <span class="text-[10px] font-body font-700 bg-blue-600 text-white px-1.5 py-0.5 rounded">{{ $part->drive_type }}</span>
                                    @elseif($part->gear_alias ?? null)
                                    @php
                                        $driveBadge = null;
                                        foreach (['4WD','4x4','AWD','2WD','4x2','FWD','RWD'] as $dt) {
                                            if (str_contains(strtoupper($part->gear_alias), str_replace('x','X',$dt))) { $driveBadge = $dt; break; }
                                        }
                                    @endphp
                                    @if($driveBadge)
                                    <span class="text-[10px] font-body font-700 bg-blue-600 text-white px-1.5 py-0.5 rounded">{{ $driveBadge }}</span>
                                    @endif
                                    @endif
                                </div>
                                @endif

                                {{-- Mileage + OEM --}}
                                <div class="flex flex-wrap gap-3 text-xs font-body text-gray-500 mb-3">
                                    @if($part->mileage)
                                        <span>🔢 {{ number_format($part->mileage) }} mi</span>
                                    @endif
                                    @if($part->oem_part_number)
                                        <span class="font-mono">OEM: {{ $part->oem_part_number }}</span>
                                    @endif
                                    @if($part->colour)
                                        <span>🎨 {{ $part->colour }}</span>
                                    @endif
                                </div>

                                {{-- Compatibility badge — NEW: uses the shared well-known
                                     year range (confirmed interchange group →
                                     PlatformDatabase generation → OemDatabase
                                     engine/trans code → raw donor year as last
                                     resort), same logic now driving the barcode
                                     tag, Compatibility Checker, and part detail
                                     page. FIXED: previously appended the raw
                                     donor year_to a second time after the
                                     compat-year fallback, which could produce
                                     garbled output like "2006–2006–2010" when
                                     the two disagreed. --}}
                                @php
                                    $wellKnown = app(\App\Services\InterchangeService::class)->wellKnownYearRange($part);
                                @endphp
                                <div class="bg-blue-50 border border-blue-100 rounded-lg px-3 py-1.5 mb-3 text-xs font-body text-blue-700">
                                    ✓ Fits: {{ $part->brand }} {{ $part->model }} {{ $wellKnown['year_from'] }}@if($wellKnown['year_to'] != $wellKnown['year_from'])–{{ $wellKnown['year_to'] }}@endif
                                    @if($part->body_style && $part->body_style !== 'N/A')
                                        · {{ $part->body_style }}
                                    @endif
                                </div>

                                <div class="mt-auto">
                                    {{-- Price — fixed, in this part's own currency --}}
                                    <div class="flex items-end justify-between mb-3">
                                        <div>
                                            <div class="font-display font-800 text-navy text-2xl leading-none tracking-wide">{{ $price }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs text-gray-400 font-body">Part ID</div>
                                            <div class="text-xs font-mono font-500 text-gray-600">{{ $part->part_code }}</div>
                                        </div>
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('parts.show', $part->id) }}"
                                           class="text-center text-xs font-body font-500 text-navy border border-navy rounded-lg px-3 py-2.5 hover:bg-navy hover:text-white transition-colors">
                                            View Details
                                        </a>
                                        <a href="https://wa.me/{{ str_contains($part->location, 'Nigeria') || str_contains($part->location,'Ghana') ? '2349155688804' : '16822563201' }}?text={{ $whatsappMsg }}"
                                           target="_blank"
                                           class="flex items-center justify-center gap-1.5 text-xs font-body font-500 bg-green-500 hover:bg-green-600 text-white rounded-lg px-3 py-2.5 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                            WhatsApp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- List view (hidden by default) --}}
                <div id="partsList" class="hidden flex flex-col gap-3">
                    @foreach($parts as $part)
                        @php
                            $photos = json_decode($part->photos ?? '[]', true) ?: [];
                            $thumb  = !empty($photos[0])
                                ? asset(config('media.prefix') . '/' . $photos[0])
                                : asset('images/parts-photo-coming-soon.jpg');
                            $hasRealPhoto = count($photos) > 0;

                            // ── FIXED PRICE — same as grid view, no conversion ──
                            $priceLocal = $part->price_local ?? $part->price_usd;
                            $currencyCode = $part->currency_code ?? 'USD';
                            $currencySymbol = match($currencyCode) {
                                'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$',
                            };
                            $priceDecimals = $currencyCode === 'NGN' ? 0 : 2;
                            $price = $currencySymbol . number_format($priceLocal, $priceDecimals);

                            $gradeClass = match($part->condition_grade) { 'A'=>'grade-a','B'=>'grade-b','C'=>'grade-c','New'=>'grade-new',default=>'grade-b' };
                            $whatsappMsg = urlencode("Hi, I'm enquiring about {$part->part_name} ({$part->part_code}) for a {$part->brand} {$part->model}. Price: {$price}.");
                        @endphp
                        <div class="part-card bg-white rounded-xl border border-gray-200 shadow-sm flex gap-4 p-4 items-start">
                            {{-- Thumb --}}
                            <a href="{{ route('parts.show', $part->id) }}" class="w-24 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                <img src="{{ $thumb ?: asset('images/coming-soon.jpg') }}" alt="{{ $part->part_name }}"
                                    class="w-full h-full {{ $hasRealPhoto ? 'object-cover' : 'object-contain p-2' }}">
                            </a>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-display font-700 text-navy text-sm tracking-wide mb-0.5">{{ $part->year_from }}@if($part->year_to!=$part->year_from)–{{ $part->year_to }}@endif {{ $part->brand }} {{ $part->model }}</div>
                                        <h3 class="font-body font-500 text-gray-600 text-sm">{{ $part->part_name }} @if($part->side && $part->side!=='N/A')<span class="text-gray-400">· {{ $part->side }}</span>@endif</h3>
                                        <div class="flex gap-3 mt-1 text-xs font-body text-gray-500">
                                            @if($part->mileage)<span>{{ number_format($part->mileage) }} mi</span>@endif
                                            @if($part->oem_part_number)<span class="font-mono">{{ $part->oem_part_number }}</span>@endif
                                            <span class="{{ $gradeClass }} px-2 py-0.5 rounded-full text-xs font-500">{{ $part->condition_grade }}</span>
                                        </div>
                                        @php
                                            $listDisplacementL = \App\Data\OemDatabase::displacementForCode($part->engine_code_oem ?? null);
                                        @endphp
                                        @if($listDisplacementL || ($part->engine_code_oem ?? null) || ($part->pin_count ?? null) || ($part->drive_type ?? null))
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @if($listDisplacementL)<span class="text-[10px] font-mono font-700 bg-gray-700 text-white px-1.5 py-0.5 rounded">{{ $listDisplacementL }}L</span>@endif
                                            @if($part->engine_code_oem ?? null)<span class="text-[10px] font-mono font-700 bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $part->engine_code_oem }}</span>@endif
                                            @if($part->pin_count ?? null)<span class="text-[10px] font-mono font-700 bg-navy text-gold px-1.5 py-0.5 rounded">{{ $part->pin_count }}-pin</span>@endif
                                            @if($part->drive_type ?? null)<span class="text-[10px] font-body font-700 bg-blue-600 text-white px-1.5 py-0.5 rounded">{{ $part->drive_type }}</span>@endif
                                        </div>
                                        @endif
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="font-display font-800 text-navy text-xl">{{ $price }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ \App\Support\HubLocations::publicLabel($part->location) }}</div>
                                        <div class="flex gap-2 mt-2">
                                            <a href="{{ route('parts.show', $part->id) }}" class="text-xs font-body border border-navy text-navy rounded-lg px-3 py-1.5 hover:bg-navy hover:text-white transition-colors">Details</a>
                                            <a href="https://wa.me/{{ str_contains($part->location, 'Nigeria') || str_contains($part->location,'Ghana') ? '2349155688804' : '16822563201' }}?text={{ $whatsappMsg }}" target="_blank" class="text-xs font-body bg-green-500 text-white rounded-lg px-3 py-1.5 hover:bg-green-600 transition-colors">WhatsApp</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-8">
                    {{ $parts->links() }}
                </div>

                {{-- Recently sold (adverts) --}}
                @include('parts._sold-ads')

                {{-- Didn't see what you need? --}}
                @include('parts._request-form', [
                    'requestVehicle' => collect($chips['vehicles'] ?? [])->pluck('label')->implode(', ') ?: trim(($filters['year'] ?? '') . ' ' . ($filters['make'] ?? '') . ' ' . ($filters['model'] ?? '')),
                    'requestPart'    => collect($chips['parts'] ?? [])->pluck('label')->implode(', ') ?: ($filters['q'] ?? ''),
                    'requestSource'  => 'search',
                    'requestOpen'    => false,
                ])

            @endif
        </main>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── VIN Decode ───────────────────────────────────────────────────────────────
const vinInput     = document.getElementById('vinInput');
const vinBtn       = document.getElementById('vinDecodeBtn');
const vinBanner    = document.getElementById('vinResultBanner');
const vinErrBanner = document.getElementById('vinErrorBanner');
const vinErrText   = document.getElementById('vinErrorText');
const vinLabel     = document.getElementById('vinVehicleLabel');
const vinDetails   = document.getElementById('vinDetailsLabel');
const vinCount     = document.getElementById('vinCharCount');
const tabVin       = document.getElementById('tabVin');
const tabBrowse    = document.getElementById('tabBrowse');
const browsePanel  = document.getElementById('browsePanel');

// ── VIN char counter ─────────────────────────────────────────────────────────
vinInput.addEventListener('input', () => {
    const len = vinInput.value.length;
    vinCount.textContent = `${len}/17`;
    vinCount.className = `absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono ${len === 17 ? 'text-green-600 font-500' : 'text-gray-400'}`;
});

vinInput.addEventListener('keydown', e => { if (e.key === 'Enter') decodeVin(); });
vinBtn.addEventListener('click', decodeVin);

// ── Decode VIN ───────────────────────────────────────────────────────────────
async function decodeVin() {
    const vin = vinInput.value.trim();
    if (vin.length !== 17) {
        showVinError('Please enter a complete 17-character VIN number.');
        return;
    }

    vinBtn.disabled = true;
    vinBtn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Decoding...`;

    try {
        const res = await fetch('{{ route('parts.vin-decode') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ vin })
        });

        const data = await res.json();

        if (!res.ok) {
            showVinError(data.error || 'VIN decode failed. Please check the number.');
            return;
        }

        const v = data.vehicle;

        // ── Vehicle label ─────────────────────────────────────────────
        vinLabel.textContent = `${v.year || ''} ${v.make || ''} ${v.model || ''}${v.trim ? ' · ' + v.trim : ''}`;

        // ── Detail line: engine size + OEM code + cylinders + drive ──
        vinDetails.textContent = [
            v.engine_l        ? v.engine_l + 'L'              : '',
            v.oem_engine_code ? '(' + v.oem_engine_code + ')' : '',
            v.engine_cyl      ? v.engine_cyl + '-Cyl'         : '',
            v.drive_type      || '',
            v.body_style      || '',
        ].filter(Boolean).join(' · ');

        // ── OEM panel (engine code, gear code, pin count) ─────────────
        const oemPanel = document.getElementById('vinOemPanel');
        if (oemPanel) {
            const badges = [];
            if (v.oem_engine_code) {
                badges.push(`<div class="flex flex-col bg-navy bg-opacity-60 rounded-xl px-3 py-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider mb-0.5">Engine Code</span>
                    <span class="text-gold font-mono font-700 text-sm">${v.oem_engine_code}</span>
                </div>`);
            }
            if (v.oem_transmission_code) {
                badges.push(`<div class="flex flex-col bg-navy bg-opacity-60 rounded-xl px-3 py-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider mb-0.5">Gear / Trans Code</span>
                    <span class="text-gold font-mono font-700 text-sm">${v.oem_transmission_code}</span>
                </div>`);
            }
            if (v.pin_count) {
                badges.push(`<div class="flex flex-col bg-navy bg-opacity-60 rounded-xl px-3 py-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider mb-0.5">Pin Count</span>
                    <span class="text-gold font-mono font-700 text-sm">${v.pin_count}-pin</span>
                </div>`);
            }
            if (v.gear_alias) {
                badges.push(`<div class="flex flex-col bg-white bg-opacity-10 rounded-xl px-3 py-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider mb-0.5">Market Name</span>
                    <span class="text-white font-body text-xs">${v.gear_alias}</span>
                </div>`);
            }

            if (badges.length > 0) {
                oemPanel.innerHTML = `<div class="text-xs text-gray-400 uppercase tracking-wider mb-2 font-body">OEM Technical Details</div>
                    <div class="flex flex-wrap gap-2">${badges.join('')}</div>`;
            } else {
                oemPanel.innerHTML = `<div class="text-xs text-gray-400 italic font-body">OEM codes not in our database — staff will verify during harvesting.</div>`;
            }
            oemPanel.classList.remove('hidden');
        }

        // ── Plain English panel ───────────────────────────────────────
        const plainPanel = document.getElementById('vinPlainPanel');
        if (plainPanel) {
            const driveMap = {
                'FWD': 'Front-Wheel Drive (FWD) — engine powers the front wheels.',
                'RWD': 'Rear-Wheel Drive (RWD) — engine powers the rear wheels.',
                'AWD': 'All-Wheel Drive (AWD) — all four wheels automatically.',
                '4WD': '4-Wheel Drive (4WD) — can switch between 2WD and 4WD.',
            };
            const driveText = Object.entries(driveMap).find(([k]) => (v.drive_type || '').includes(k))?.[1] || '';
            const parts = [
                v.engine_l && v.engine_cyl ? `This is a ${v.engine_l}L ${v.engine_cyl}-cylinder engine.` : '',
                driveText,
                v.oem_engine_code ? `Engine code <strong class="text-white">${v.oem_engine_code}</strong> — the internal name used by mechanics and parts dealers.` : '',
                v.oem_transmission_code ? `Transmission code <strong class="text-white">${v.oem_transmission_code}</strong>${v.pin_count ? ` · ${v.pin_count}-pin connector` : ''}.` : '',
                v.pin_count ? `A ${v.pin_count}-pin transmission is required for this vehicle — important when sourcing a replacement.` : '',
            ].filter(Boolean).join(' ');
            if (parts) {
                plainPanel.innerHTML = parts;
                plainPanel.classList.remove('hidden');
            }
        }

        vinBanner.classList.remove('hidden');
        vinErrBanner.classList.add('hidden');

        // ── Populate Browse dropdowns (no auto-submit) ────────────────
        if (v.make) document.getElementById('makeSelect').value = v.make.toUpperCase();
        if (v.model) {
            await loadModels(v.make.toUpperCase());
            document.getElementById('modelSelect').value = v.model.toUpperCase();
        }
        if (v.year) document.getElementById('yearSelect').value = v.year;

        // Show browse panel
        browsePanel.classList.remove('hidden');
        tabBrowse.className = 'tab-btn bg-gold text-navy font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide';
        tabVin.className    = 'tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors';

    } catch (err) {
        showVinError('Network error. Please try again.');
    } finally {
        vinBtn.disabled = false;
        vinBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> DECODE VIN`;
    }
}

function showVinError(msg) {
    vinErrText.textContent = msg;
    vinErrBanner.classList.remove('hidden');
    vinBanner.classList.add('hidden');
    setTimeout(() => vinErrBanner.classList.add('hidden'), 5000);
}

document.getElementById('vinClearBtn')?.addEventListener('click', () => {
    vinInput.value = '';
    vinBanner.classList.add('hidden');
    vinCount.textContent = '0/17';
    const oemPanel = document.getElementById('vinOemPanel');
    const plainPanel = document.getElementById('vinPlainPanel');
    if (oemPanel) oemPanel.classList.add('hidden');
    if (plainPanel) plainPanel.classList.add('hidden');
});

// ── Tab switching ─────────────────────────────────────────────────────────────
tabVin.addEventListener('click', () => {
    browsePanel.classList.add('hidden');
    tabVin.className    = 'tab-btn bg-gold text-navy font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide';
    tabBrowse.className = 'tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors';
});

tabBrowse.addEventListener('click', () => {
    browsePanel.classList.remove('hidden');
    tabBrowse.className = 'tab-btn bg-gold text-navy font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide';
    tabVin.className    = 'tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors';
});

// Show browse panel if filters already active
@if(!empty($filters['make']) || !empty($filters['model']) || !empty($filters['vehicles']) || !empty($filters['parts']))
    browsePanel.classList.remove('hidden');
    tabBrowse.className = 'tab-btn bg-gold text-navy font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide';
    tabVin.className    = 'tab-btn bg-white bg-opacity-10 text-white hover:bg-opacity-20 font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors';
@endif

// ── Model dropdown cascade ────────────────────────────────────────────────────
document.getElementById('makeSelect').addEventListener('change', async function() {
    await loadModels(this.value.toUpperCase());
});

// (Picking a model no longer reloads the page by itself — press ADD VEHICLE or SEARCH PARTS,
//  so several vehicles can be added to one search.)

async function loadModels(make) {
    const sel = document.getElementById('modelSelect');
    if (!make) {
        sel.innerHTML = '<option value="">All Models</option>';
        return;
    }
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make.toUpperCase())}`);
        const data = await res.json();
        sel.innerHTML = '<option value="">All Models</option>' +
            (data.models || []).map(m => `<option value="${m}">${m}</option>`).join('');
    } catch(e) {
        sel.innerHTML = '<option value="">All Models</option>';
    }
}


document.getElementById('categorySelect').addEventListener('change', () => {
    const f = document.getElementById('browseForm');
    if (f.requestSubmit) f.requestSubmit(); else f.submit();
});

// ── Grid / List toggle ────────────────────────────────────────────────────────
const gridBtn   = document.getElementById('gridViewBtn');
const listBtn   = document.getElementById('listViewBtn');
const partsGrid = document.getElementById('partsGrid');
const partsList = document.getElementById('partsList');

if (gridBtn && listBtn) {
    gridBtn.addEventListener('click', () => {
        partsGrid?.classList.remove('hidden');
        partsList?.classList.add('hidden');
        gridBtn.className = 'view-toggle px-3 py-2 bg-navy text-white';
        listBtn.className = 'view-toggle px-3 py-2 bg-white text-gray-500 hover:bg-gray-50';
        localStorage.setItem('az_view', 'grid');
    });

    listBtn.addEventListener('click', () => {
        partsGrid?.classList.add('hidden');
        partsList?.classList.remove('hidden');
        listBtn.className = 'view-toggle px-3 py-2 bg-navy text-white';
        gridBtn.className = 'view-toggle px-3 py-2 bg-white text-gray-500 hover:bg-gray-50';
        localStorage.setItem('az_view', 'list');
    });

    if (localStorage.getItem('az_view') === 'list') listBtn.click();
}

// ── Filter accordion ──────────────────────────────────────────────────────────
document.querySelectorAll('.filter-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const target  = document.getElementById(btn.dataset.target);
        const chevron = btn.querySelector('svg');
        const isOpen  = !target.classList.contains('hidden');
        target.classList.toggle('hidden');
        chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
    });
});

// ── Callahan-style search: vehicle chips, part chips and the parts typeahead ──────
(() => {
    const MAX = 10;
    const form      = document.getElementById('browseForm');
    const makeSel   = document.getElementById('makeSelect');
    const modelSel  = document.getElementById('modelSelect');
    const yearSel   = document.getElementById('yearSelect');
    const vBox      = document.getElementById('vehicleChips');
    const pBox      = document.getElementById('partChips');
    const inputs    = document.getElementById('chipInputs');
    const partInput = document.getElementById('partInput');
    const suggest   = document.getElementById('partSuggest');
    if (!form || !partInput) return;

    const state = { vehicles: @json($initVehicles), parts: @json($initParts) };

    const vehicleLabel = v => v.split('|').filter(x => x).join(' ');

    function chip(text, cls, onRemove) {
        const el = document.createElement('span');
        el.className = 'inline-flex items-center gap-1.5 text-xs font-body font-600 px-3 py-1.5 rounded-full ' + cls;
        const label = document.createElement('span'); label.textContent = text;
        const x = document.createElement('button'); x.type = 'button'; x.textContent = '\u00d7';
        x.className = 'text-base leading-none opacity-80 hover:opacity-100'; x.setAttribute('aria-label', 'Remove ' + text);
        x.addEventListener('click', onRemove);
        el.append(label, x);
        return el;
    }
    function hidden(name, value) {
        const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = value; return i;
    }
    function render() {
        vBox.replaceChildren(); pBox.replaceChildren(); inputs.replaceChildren();
        state.vehicles.forEach((v, i) => {
            vBox.append(chip(vehicleLabel(v), 'bg-navy text-white', () => { state.vehicles.splice(i, 1); render(); }));
            inputs.append(hidden('v[]', v));
        });
        state.parts.forEach((p, i) => {
            pBox.append(chip(p, 'bg-gold text-navy', () => { state.parts.splice(i, 1); render(); }));
            inputs.append(hidden('part[]', p));
        });
    }

    function addVehicle() {
        const make = makeSel.value.trim(), model = modelSel.value.trim(), year = yearSel.value.trim();
        if (!make && !model && !year) return false;
        if (state.vehicles.length >= MAX) { alert('You can search up to ' + MAX + ' vehicles at once.'); return false; }
        const v = year + '|' + make + '|' + model;
        if (!state.vehicles.some(x => x.toLowerCase() === v.toLowerCase())) state.vehicles.push(v);
        makeSel.value = ''; yearSel.value = ''; modelSel.innerHTML = '<option value="">All Models</option>';
        render();
        return true;
    }
    function addPart(name) {
        name = (name || '').trim();
        if (!name) return;
        if (state.parts.length >= MAX) { alert('You can search up to ' + MAX + ' parts at once.'); return; }
        if (!state.parts.some(x => x.toLowerCase() === name.toLowerCase())) state.parts.push(name);
        partInput.value = ''; suggest.classList.add('hidden');
        render();
    }

    document.getElementById('addVehicleBtn').addEventListener('click', () => { if (!addVehicle()) makeSel.focus(); });

    // Searching with a vehicle still selected, or part text still typed, counts them too.
    form.addEventListener('submit', () => { addVehicle(); if (partInput.value.trim()) addPart(partInput.value); });

    // ── Typeahead: part names that exist for the chosen vehicle(s), with how many are in stock ──
    let timer = null;
    async function fetchSuggestions() {
        const params = new URLSearchParams({ mode: 'parts', q: partInput.value.trim(), make: makeSel.value, model: modelSel.value, year: yearSel.value });
        state.vehicles.forEach(v => params.append('v[]', v));
        try {
            const res  = await fetch(`{{ route('parts.models') }}?` + params.toString());
            const data = await res.json();
            showSuggestions(data.parts || []);
        } catch (e) { suggest.classList.add('hidden'); }
    }
    function showSuggestions(list) {
        const taken = state.parts.map(x => x.toLowerCase());
        list = list.filter(p => !taken.includes(p.name.toLowerCase()));
        suggest.replaceChildren();
        const typed = partInput.value.trim();

        list.forEach(p => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'w-full flex items-center justify-between gap-3 px-3.5 py-2 text-sm font-body text-left hover:bg-gray-50';
            const n = document.createElement('span'); n.className = 'font-600 text-navy'; n.textContent = p.name;
            const c = document.createElement('span'); c.className = 'text-xs text-gray-400 whitespace-nowrap'; c.textContent = p.count + (p.count === 1 ? ' in stock' : ' in stock');
            b.append(n, c);
            b.addEventListener('mousedown', e => { e.preventDefault(); addPart(p.name); });
            suggest.append(b);
        });
        if (!list.length && typed) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'w-full px-3.5 py-2 text-sm font-body text-left text-gray-500 hover:bg-gray-50';
            b.textContent = 'No match in stock for this vehicle — press Enter to search "' + typed + '" anyway';
            b.addEventListener('mousedown', e => { e.preventDefault(); addPart(typed); });
            suggest.append(b);
        }
        suggest.classList.toggle('hidden', !suggest.childElementCount);
    }
    partInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(fetchSuggestions, 200); });
    partInput.addEventListener('focus', fetchSuggestions);
    partInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addPart(partInput.value); }
        if (e.key === 'Escape') suggest.classList.add('hidden');
    });
    document.addEventListener('click', e => { if (!suggest.contains(e.target) && e.target !== partInput) suggest.classList.add('hidden'); });

    render();
})();
</script>
@endpush
