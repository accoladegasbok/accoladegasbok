{{-- FILE: resources/views/admin/inventory/marketplace-export.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Marketplace Export — ' . $part->part_code)
@section('page-title', 'Marketplace Listing Export')
@section('page-sub', $part->part_name . ' · ' . $part->part_code)

@section('content')
<div class="max-w-3xl">

  <div class="flex items-center gap-2 mb-5">
    <span class="text-xs font-700 px-3 py-1.5 rounded-full {{ $region === 'USA' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
      {{ $region === 'USA' ? '🇺🇸 USA Region' : '🌍 West Africa Region' }}
    </span>
    <span class="text-xs text-gray-400">— based on location: {{ $part->location }}</span>
  </div>

  {{-- Suggested platforms for this region --}}
  <div class="stat-card mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Suggested Platforms</h2>
    <div class="flex flex-wrap gap-2">
      @foreach($platforms as $p)
      <span class="text-xs font-600 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-navy">{{ $p['label'] }}</span>
      @endforeach
    </div>
    <p class="text-xs text-gray-400 mt-3">
      No platform here offers a public API for individual listings except eBay — copy the fields below directly into each platform's own posting form for now.
    </p>
  </div>

  {{-- Title --}}
  <div class="stat-card mb-5">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Title</h2>
      <button type="button" onclick="copyField('titleField', this)" class="text-xs font-600 text-blue-600 hover:text-blue-800">📋 Copy</button>
    </div>
    <textarea id="titleField" readonly rows="2" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-gray-50">{{ $title }}</textarea>
    <div class="flex gap-4 mt-2 text-[11px]">
      @foreach($platforms as $p)
      <span class="{{ strlen($title) > $p['title_limit'] ? 'text-red-500 font-700' : 'text-gray-400' }}">
        {{ $p['label'] }}: {{ strlen($title) }}/{{ $p['title_limit'] }}{{ strlen($title) > $p['title_limit'] ? ' — over limit, will be cut' : '' }}
      </span>
      @endforeach
    </div>
  </div>

  {{-- Description --}}
  <div class="stat-card mb-5">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Description</h2>
      <button type="button" onclick="copyField('descField', this)" class="text-xs font-600 text-blue-600 hover:text-blue-800">📋 Copy</button>
    </div>
    <textarea id="descField" readonly rows="14" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-gray-50 whitespace-pre-wrap">{{ $description }}</textarea>
  </div>

  {{-- Photos --}}
  <div class="stat-card mb-5">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Photos ({{ $photos->count() }})</h2>
      @if($photos->isNotEmpty())
      <button type="button" onclick="copyField('photosField', this)" class="text-xs font-600 text-blue-600 hover:text-blue-800">📋 Copy All Links</button>
      @endif
    </div>
    @if($photos->isEmpty())
    <p class="text-xs text-gray-400">No photos on file for this part — add some from the edit page first for a listing that actually gets clicks.</p>
    @else
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-3">
      @foreach($photos as $photoUrl)
      <a href="{{ $photoUrl }}" target="_blank"><img src="{{ $photoUrl }}" class="w-full h-20 object-cover rounded-lg border border-gray-200 hover:border-gold transition-colors"></a>
      @endforeach
    </div>
    <textarea id="photosField" readonly rows="3" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-mono bg-gray-50">{{ $photos->implode("\n") }}</textarea>
    @endif
  </div>

  {{-- Permalink --}}
  <div class="stat-card mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-2">Live Page Link (already included in the description above)</h2>
    <div class="flex items-center gap-2">
      <input readonly value="{{ $permalink }}" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono bg-gray-50">
      <a href="{{ $permalink }}" target="_blank" class="text-xs font-600 text-navy border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">View Live →</a>
    </div>
  </div>

  <div class="flex gap-3 pb-8">
    <a href="{{ route('admin.inventory.edit', $part->id) }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">← Back to Part</a>
  </div>

</div>

<script>
function copyField(id, btn) {
    const el = document.getElementById(id);
    el.select();
    el.setSelectionRange(0, 999999);
    navigator.clipboard.writeText(el.value).then(() => {
        const original = btn.textContent;
        btn.textContent = '✓ Copied';
        setTimeout(() => btn.textContent = original, 1500);
    });
}
</script>
@endsection
