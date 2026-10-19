@extends('layouts.app')

@section('title', 'Sold Parts - Last 12 Months | Auto Zenith Parts')
@section('robots', 'noindex,follow')
@section('meta_desc', 'Parts and items Auto Zenith Parts has sold in the last 12 months. Need one like it? Ask us and we will find it for you.')

@section('content')
<div class="bg-navy">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 text-center">
    <h1 class="font-display font-800 text-white text-3xl sm:text-4xl tracking-wide">SOLD IN THE LAST 12 MONTHS</h1>
    <p class="text-gray-300 font-body text-sm mt-2">Proof that our stock moves. Need one like it? We will find it for you.</p>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <form method="GET" action="{{ route('parts.sold') }}" class="flex flex-wrap gap-2 mb-6">
    <input type="text" name="q" value="{{ $text }}" placeholder="Search sold items (part, make, model)..."
           class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    <select name="category" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white">
      <option value="">All categories</option>
      @foreach($categories as $c)<option value="{{ $c }}" {{ $category === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
    </select>
    <button class="bg-navy text-white text-sm font-body px-5 py-2.5 rounded-xl">Search</button>
    <a href="{{ route('parts.search') }}" class="border border-gray-200 text-gray-600 text-sm font-body px-4 py-2.5 rounded-xl hover:bg-gray-50">Back to in-stock parts</a>
  </form>

  @if($items->count() === 0)
    <div class="text-center py-16 text-gray-500 font-body">No sold items match. <a href="{{ route('parts.search') }}" class="underline text-navy">Search what is in stock</a> or <a href="/#contact" class="underline text-navy">ask us to source it</a>.</div>
  @else
  <style>
    .sold-mark { display:inline-block; transform:rotate(-18deg); border:4px solid rgba(200,30,30,.88); color:rgba(200,30,30,.9);
                 background:rgba(255,255,255,.35); font-weight:800; font-size:30px; letter-spacing:.18em; padding:0 14px 0 18px; line-height:1.25; }
    .sold-card img { filter:saturate(.75) brightness(.95); }
  </style>
  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($items as $sp)
    @php
        $spPhotos = json_decode($sp->photos ?? '[]', true) ?: [];
        $spThumb  = !empty($spPhotos[0]) ? asset(config('media.prefix') . '/' . $spPhotos[0]) : asset('images/parts-photo-coming-soon.jpg');
        $spYears  = $sp->year_from . ((!empty($sp->year_to) && $sp->year_to != $sp->year_from) ? '–' . $sp->year_to : '');
        $spVehicle = trim(($sp->year_from ? $spYears . ' ' : '') . $sp->brand . ' ' . $sp->model);
    @endphp
    <div class="sold-card bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm flex flex-col">
      <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
        <img src="{{ $spThumb }}" alt="{{ $sp->part_name }} - sold" loading="lazy" class="w-full h-full object-cover">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none"><span class="sold-mark">SOLD</span></div>
      </div>
      <div class="p-3 flex flex-col flex-1">
        <div class="font-display font-700 text-navy text-sm leading-tight">{{ $sp->part_name }}</div>
        <div class="text-xs text-gray-500 font-body mt-0.5">{{ $spVehicle }}</div>
        <div class="text-[11px] text-gray-400 font-body mt-0.5">Sold {{ \Carbon\Carbon::parse($sp->sold_at ?? $sp->updated_at)->format('M j, Y') }}</div>
        <a href="{{ route('parts.search') }}?q={{ urlencode(trim($sp->part_name . ' ' . $sp->brand . ' ' . $sp->model)) }}"
           class="mt-auto pt-3 text-xs font-display font-700 text-gold hover:text-yellow-600 underline">Find one like this</a>
      </div>
    </div>
    @endforeach
  </div>
  <div class="mt-6">{{ $items->links() }}</div>
  @endif
</div>
@endsection
