@extends('layouts.app')
@php use Illuminate\Support\Arr; @endphp

@section('title', 'Other Items — Consumables, Electronics & More | Auto Zenith Parts')
@section('meta_desc', 'Browse consumables, electronics, computers and other items from Auto Zenith Parts — oils, fluids, filters, and more, across USA, Nigeria and Ghana locations.')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════
     HEADER — simpler than /parts (no VIN decode, no vehicle browse —
     these items aren't tied to a specific make/model/year)
═══════════════════════════════════════════════════════════════════ --}}
<div class="bg-navy relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:linear-gradient(rgba(255,255,255,.15) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.15) 1px,transparent 1px);background-size:32px 32px;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="text-center mb-7">
            <h1 class="font-display font-800 text-white text-4xl sm:text-5xl tracking-wide leading-tight">
                OTHER ITEMS,<br><span class="text-gold">ALL IN ONE PLACE</span>
            </h1>
            <p class="text-gray-300 font-body text-sm mt-2">Oils, fluids, filters, electronics, computers and more — not tied to a specific vehicle</p>
        </div>

        {{-- ── Trust stat ──────────────────────────────────────────── --}}
        <div class="max-w-xs mx-auto mb-7">
            <div class="bg-white bg-opacity-10 rounded-xl px-3 py-3 text-center">
                <div class="font-display font-800 text-gold text-2xl">{{ number_format($totalAvailable) }}+</div>
                <div class="text-xs text-gray-300 font-body mt-0.5">Items In Stock Now</div>
            </div>
        </div>

        {{-- ── Category tabs — the "separate button" grouping this whole
             page exists for, rather than mixing into /parts ──────────── --}}
        <div class="flex justify-center gap-2 mt-5 flex-wrap">
            <a href="{{ route('other-items') }}" class="tab-btn {{ empty($filters['category']) ? 'bg-gold text-navy' : 'bg-white bg-opacity-10 text-white hover:bg-opacity-20' }} font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors inline-block">
                ALL ITEMS
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('other-items') }}?category={{ $cat }}" class="tab-btn {{ ($filters['category'] ?? '') === $cat ? 'bg-gold text-navy' : 'bg-white bg-opacity-10 text-white hover:bg-opacity-20' }} font-display font-700 text-xs px-5 py-2 rounded-full tracking-wide transition-colors inline-block">
                {{ $cat === 'Consumable' ? 'OILS, FLUIDS & FILTERS' : strtoupper($cat) }}
            </a>
            @endforeach
        </div>

        {{-- ── Special Order Notice — same as /parts ────────────────── --}}
        <div class="max-w-3xl mx-auto mt-6">
            <div class="bg-gold bg-opacity-10 border border-gold border-opacity-40 rounded-2xl px-5 py-4 flex flex-col sm:flex-row items-center gap-3 text-center sm:text-left">
                <div class="text-2xl">📦</div>
                <div class="flex-1">
                    <div class="font-display font-700 text-white text-sm">Can't find what you need?</div>
                    <div class="text-xs text-gray-300 font-body mt-0.5">Send us a message and we'll confirm availability and pricing.</div>
                </div>
                <button type="button" onclick="openWaPicker()" class="bg-gold text-navy font-display font-700 text-xs px-5 py-2.5 rounded-xl hover:bg-yellow-400 transition-colors whitespace-nowrap">
                    Send Us a Message
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     FILTER BAR — Search / Location / Price / Condition (same fields
     searchParts() already supports; no vehicle-specific filters here)
═══════════════════════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <form method="GET" action="{{ route('other-items') }}" id="otherItemsFilterForm">
            @if(!empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Search</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Item name..."
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                </div>
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Location</label>
                    <select name="location" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">All Locations</option>
                        <option value="USA" {{ ($filters['location'] ?? '') === 'USA' ? 'selected' : '' }}>🇺🇸 USA</option>
                        <option value="Nigeria" {{ ($filters['location'] ?? '') === 'Nigeria' ? 'selected' : '' }}>🇳🇬 Nigeria</option>
                        <option value="Ghana" {{ ($filters['location'] ?? '') === 'Ghana' ? 'selected' : '' }}>🇬🇭 Ghana</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Condition</label>
                    <select name="condition" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        <option value="">Any Condition</option>
                        <option value="New" {{ ($filters['condition'] ?? '') === 'New' ? 'selected' : '' }}>New</option>
                        <option value="A" {{ ($filters['condition'] ?? '') === 'A' ? 'selected' : '' }}>Grade A</option>
                        <option value="B" {{ ($filters['condition'] ?? '') === 'B' ? 'selected' : '' }}>Grade B</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-navy hover:bg-navy-light text-white font-display font-700 text-sm px-4 py-2.5 rounded-lg tracking-wide transition-colors">
                        SEARCH
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     RESULTS
═══════════════════════════════════════════════════════════════════ --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="font-display font-700 text-navy text-lg">
                @if($total > 0){{ number_format($total) }} Item{{ $total === 1 ? '' : 's' }} Found
                @else No Items Found
                @endif
            </h2>
            @if(!empty($filters['q']))
            <p class="text-sm text-gray-500 font-body mt-0.5">Showing results for "<strong class="text-navy">{{ $filters['q'] }}</strong>"</p>
            @endif
        </div>
    </div>

    @if($parts->isEmpty())
        <div class="text-center py-16">
            <div class="text-5xl mb-3">📦</div>
            <p class="text-gray-500 font-body">No items match your search. Try clearing a filter, or send us a message — we may be able to source it.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($parts as $part)
                @php
                    $photos     = json_decode($part->photos ?? '[]', true) ?: [];
                    $thumb      = !empty($photos[0])
                        ? asset(config('media.prefix') . '/' . $photos[0])
                        : asset('images/parts-photo-coming-soon.jpg');

                    $priceLocal   = $part->price_local ?? $part->price_usd;
                    $currencyCode = $part->currency_code ?? 'USD';
                    $currencySymbol = match($currencyCode) { 'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$' };
                    $priceDecimals  = $currencyCode === 'NGN' ? 0 : 2;
                    $price = $currencySymbol . number_format($priceLocal, $priceDecimals);

                    $locFlag = match(true) {
                        str_contains($part->location, 'Nigeria') || str_contains($part->location, 'Lagos') => '🇳🇬',
                        str_contains($part->location, 'Ghana')   => '🇬🇭',
                        default => '🇺🇸',
                    };

                    // Consumables sometimes fold a custom brand into
                    // part_name as "Name - Product..." (see
                    // InventoryController) — same display recovery
                    // used on the admin side, kept consistent here.
                    $displayBrand = $part->brand;
                    if ($part->brand === 'Generic' && str_contains($part->part_name, ' - ')) {
                        $displayBrand = explode(' - ', $part->part_name, 2)[0];
                    }

                    $whatsappMsg = urlencode("Hi, I'm enquiring about the {$part->part_name} ({$part->part_code}). Location: {$part->location}. Price: {$price}. Is this available?");
                @endphp

                <div class="part-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                    <a href="{{ route('parts.show', $part->id) }}" class="block relative bg-gray-100 aspect-[4/3] overflow-hidden">
                        <img src="{{ $thumb }}" alt="{{ $part->part_name }}"
                            class="w-full h-full {{ !empty($photos[0]) ? 'object-cover hover:scale-105' : 'object-contain p-4' }} transition-transform duration-300" loading="lazy">
                        <div class="absolute top-2 left-2">
                            <span class="inline-block text-xs font-body font-500 px-2.5 py-1 rounded-full bg-white bg-opacity-90 text-navy">
                                {{ $part->part_category }}
                            </span>
                        </div>
                    </a>

                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="font-display font-700 text-navy text-sm tracking-wide">{{ $displayBrand }}</div>
                            <span class="text-xs font-body font-500 px-2 py-0.5 rounded-full flex-shrink-0 bg-gray-100 text-gray-600">
                                {{ $locFlag }} {{ explode(' ', $part->location)[0] }}
                            </span>
                        </div>

                        <h3 class="font-body font-500 text-gray-600 text-sm leading-tight mb-3">
                            <a href="{{ route('parts.show', $part->id) }}" class="hover:text-az-blue transition-colors">
                                {{ $part->part_name }}
                            </a>
                        </h3>

                        <div class="mt-auto">
                            <div class="flex items-end justify-between mb-3">
                                <div class="font-display font-800 text-navy text-2xl leading-none tracking-wide">{{ $price }}</div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 font-body">Item ID</div>
                                    <div class="text-xs font-mono font-500 text-gray-600">{{ $part->part_code }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('parts.show', $part->id) }}"
                                   class="text-center text-xs font-body font-500 text-navy border border-navy rounded-lg px-3 py-2.5 hover:bg-navy hover:text-white transition-colors">
                                    View Details
                                </a>
                                <a href="https://wa.me/{{ str_contains($part->location, 'Nigeria') || str_contains($part->location,'Ghana') ? '2349155688804' : '16822563201' }}?text={{ $whatsappMsg }}"
                                   target="_blank"
                                   class="flex items-center justify-center gap-1.5 text-xs font-body font-500 bg-green-500 hover:bg-green-600 text-white rounded-lg px-3 py-2.5 transition-colors">
                                    WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $parts->appends($filters)->links() }}
        </div>
    @endif
</div>

@endsection
