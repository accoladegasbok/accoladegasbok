{{-- FILE: resources/views/parts/categories.blade.php --}}
@extends('layouts.app')

@section('title', 'Shop Used Auto Parts by Category | Auto Zenith Parts')
@section('meta_desc', 'Browse used auto parts by group: engines, transmissions, suspension, brakes, wheels, body, interior, electrical, cooling, fuel and exhaust, airbags. Tested, graded, shipped from USA to Nigeria and Ghana.')

@section('content')
<div class="bg-navy">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 text-center">
    <h1 class="font-display font-800 text-white text-3xl sm:text-4xl tracking-wide">SHOP BY PART GROUP</h1>
    <p class="text-gray-300 font-body text-sm mt-2">Pick a group, then the part. Next you choose your vehicle and see what fits.</p>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-10">
    @foreach($groups as $g)
      <a href="#g-{{ $g['tile']['slug'] }}" class="block border border-gray-200 rounded-2xl p-4 hover:border-yellow-400 hover:shadow-sm transition">
        <div class="text-2xl mb-1">{{ $g['tile']['icon'] }}</div>
        <div class="font-display font-700 text-navy text-sm tracking-wide leading-tight">{{ $g['tile']['label'] }}</div>
        <div class="text-xs font-body text-gray-500 mt-1">{{ $g['count'] > 0 ? number_format($g['count']) . ' in stock' : 'Ask us to source' }}</div>
      </a>
    @endforeach
  </div>

  @foreach($groups as $g)
    <section id="g-{{ $g['tile']['slug'] }}" class="mb-8 scroll-mt-24">
      <div class="flex items-end justify-between gap-3 mb-2">
        <h2 class="font-display font-700 text-navy text-xl tracking-wide">{{ $g['tile']['icon'] }} {{ $g['tile']['label'] }}</h2>
        <a href="{{ $g['tile']['url'] }}" class="text-sm font-body text-az-blue underline whitespace-nowrap">See all {{ $g['tile']['label'] }}</a>
      </div>
      <p class="text-sm font-body text-gray-500 mb-3">{{ $g['tile']['blurb'] }}</p>

      @if(count($g['types']))
        <div class="flex flex-wrap gap-2">
          @foreach($g['types'] as $t)
            <a href="{{ $t['url'] }}" class="border border-gray-200 rounded-full px-3 py-1.5 text-sm font-body text-gray-700 hover:border-yellow-400 hover:bg-yellow-50">
              {{ $t['name'] }} <span class="text-gray-400 text-xs">({{ $t['count'] }})</span>
            </a>
          @endforeach
        </div>
      @else
        <p class="text-sm font-body text-gray-500">Nothing listed in this group right now. <a href="{{ route('parts.search') }}" class="underline text-navy">Search all parts</a> or ask us and we will find it.</p>
      @endif
    </section>
  @endforeach

  <div class="mt-10 rounded-2xl border border-gray-200 p-5 text-sm font-body text-gray-600">
    Looking for oils, filters or other items? <a href="{{ route('other-items') }}" class="underline text-navy">Browse Oils, Filters &amp; Other Items</a>.
  </div>
</div>
@endsection
