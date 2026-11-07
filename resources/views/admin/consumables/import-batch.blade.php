{{-- FILE: resources/views/admin/consumables/import-batch.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Add Existing Product')
@section('page-title', 'Add Existing Product')
@section('page-sub', 'Already in stock somewhere? Pick it, add a new batch or stock it at another location - nothing is typed or photographed again')

@section('content')
@php $flat = collect($grouped)->flatten(1)->values(); @endphp

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif

<div class="flex flex-wrap items-center gap-3 mb-4">
  <span class="text-sm text-gray-500">All items are brand new.</span>
  <a href="{{ route('admin.inventory.consumable.create') }}" class="ml-auto bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-navy-light">+ Create New Product (first time only)</a>
</div>

<form method="POST" action="{{ route('admin.inventory.consumable.importbatch.store') }}" class="grid lg:grid-cols-3 gap-5">
  @csrf
  <input type="hidden" name="item_id" id="itemId" value="{{ old('item_id') }}">

  {{-- 1. pick the product (photo + name) --}}
  <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
    <div class="flex gap-2 mb-3">
      <input type="text" id="q" placeholder="Search products already added..." class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm">
      <select id="catF" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="">All categories</option>
        @foreach(array_keys($grouped) as $c)<option>{{ $c }}</option>@endforeach
      </select>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 max-h-[560px] overflow-y-auto pr-1" id="grid">
      @foreach($flat as $it)
      <button type="button" data-id="{{ $it['id'] }}" data-cat="{{ $it['category'] }}" data-text="{{ strtolower($it['name'].' '.$it['brand'].' '.$it['code']) }}"
              class="pick text-left border-2 border-gray-100 rounded-xl overflow-hidden bg-white hover:border-yellow-400 transition-colors">
        <div class="aspect-square bg-white flex items-center justify-center">
          @if($it['photo'])<img src="{{ $it['photo'] }}" class="w-full h-full object-contain" loading="lazy" alt="">
          @else<span class="text-[10px] text-gray-300">No photo yet</span>@endif
        </div>
        <div class="p-2">
          <div class="text-xs font-700 text-navy leading-tight">{{ $it['name'] }}</div>
          <div class="text-[10px] text-gray-400">{{ $it['brand'] }}</div>
          <div class="text-[10px] text-gray-500 mt-0.5">{{ $it['code'] }} &middot; {{ $it['total'] }} in stock</div>
        </div>
      </button>
      @endforeach
      @if($flat->isEmpty())<div class="col-span-full text-sm text-gray-400 py-8 text-center">Nothing added yet - use Create New Product.</div>@endif
    </div>
    <div class="text-[11px] text-gray-400 mt-2">Not in the list? Only then use <b>Create New Product</b>.</div>
  </div>

  {{-- 2. batch details --}}
  <div class="space-y-4">
    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
      <div class="text-xs font-700 text-gray-500 uppercase mb-2">Stock now</div>
      <div id="stockBox" class="text-sm text-gray-400">Select a product to see its quantity at every location.</div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm space-y-3">
      <div>
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Location receiving the batch</label>
        <select name="location" id="locSel" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white">
          <option value="">Select location...</option>
          @foreach($stockLocations as $l)<option value="{{ $l }}" {{ old('location') === $l ? 'selected' : '' }}>{{ $l }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Quantity in this batch</label>
        <input type="number" name="qty" min="1" required value="{{ old('qty') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
      </div>
      <div>
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Selling price</label>
        <input type="number" step="0.01" min="0" name="price_local" id="priceIn" value="{{ old('price_local') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
        <div id="priceHint" class="text-[11px] text-gray-400 mt-1"></div>
      </div>
      <div>
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Supplier invoice / lot (optional)</label>
        <input type="text" name="supplier_ref" maxlength="80" value="{{ old('supplier_ref') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
      </div>
      <button class="w-full bg-gold text-navy font-display font-700 text-sm py-2.5 rounded-xl hover:bg-yellow-400 transition-colors">Add batch</button>
      <a href="{{ route('admin.inventory.consumable.index') }}" class="block text-center text-xs text-gray-500 underline">Back to consumables</a>
    </div>
  </div>
</form>

<script>
const ITEMS = @json($flat->keyBy('id'));
const sym = {NGN:'₦', GHS:'GH₵', USD:'$'};
const idIn = document.getElementById('itemId'), locSel = document.getElementById('locSel');
function select(id) {
  idIn.value = id;
  document.querySelectorAll('.pick').forEach(b => b.classList.toggle('border-yellow-400', b.dataset.id === String(id)));
  refresh();
}
function refresh() {
  const it = ITEMS[idIn.value], box = document.getElementById('stockBox');
  const hint = document.getElementById('priceHint'), inp = document.getElementById('priceIn');
  if (!it) { box.textContent = 'Select a product to see its quantity at every location.'; hint.textContent = ''; return; }
  let h = '<div class="font-700 text-navy">' + it.name + (it.brand ? ' - ' + it.brand : '') + '</div><div class="text-[11px] text-gray-400 mb-2">' + it.code + '</div>';
  Object.entries(it.locations).forEach(([l, q]) => { h += '<div class="flex justify-between text-xs py-0.5"><span>' + l + '</span><b>' + q + '</b></div>'; });
  h += '<div class="flex justify-between text-sm border-t border-gray-100 mt-2 pt-2"><span>Total</span><b>' + it.total + '</b></div>';
  box.innerHTML = h;
  const p = it.prices[locSel.value];
  if (locSel.value && p) { hint.textContent = 'Already stocked here - price stays (' + (sym[p.cur] || '') + p.price.toLocaleString() + '). Leave blank.'; inp.required = false; }
  else if (locSel.value) { hint.textContent = 'First time at this location - enter its selling price.'; inp.required = true; }
  else { hint.textContent = ''; inp.required = false; }
}
document.querySelectorAll('.pick').forEach(b => b.addEventListener('click', () => select(b.dataset.id)));
locSel.addEventListener('change', refresh);
function filt() {
  const q = document.getElementById('q').value.toLowerCase().trim(), c = document.getElementById('catF').value;
  document.querySelectorAll('.pick').forEach(b => { b.style.display = ((!q || b.dataset.text.includes(q)) && (!c || b.dataset.cat === c)) ? '' : 'none'; });
}
document.getElementById('q').addEventListener('input', filt); document.getElementById('catF').addEventListener('change', filt);
document.querySelector('form').addEventListener('submit', e => { if (!idIn.value) { e.preventDefault(); alert('Pick a product first.'); } });
if (idIn.value) select(idIn.value);
</script>
@endsection
