{{-- FILE: resources/views/admin/consumables/import-batch.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Import New Batch')
@section('page-title', 'Import New Batch')
@section('page-sub', 'New stock of an item already in the system - nothing is typed or photographed again')

@section('content')
@php $flat = collect($grouped)->flatten(1)->values(); @endphp

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ $errors->first() }}</div>@endif

<div class="grid md:grid-cols-3 gap-5 max-w-5xl">
  <form method="POST" action="{{ route('admin.inventory.consumable.importbatch.store') }}" class="md:col-span-2 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-4">
    @csrf
    <div class="text-xs text-gray-500">All items here are <b>brand new</b>. Pick the product from the list, choose where the batch is going, and enter how many arrived. A batch number is generated automatically.</div>

    <div>
      <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Item</label>
      <select name="item_id" id="itemSel" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white">
        <option value="">Select a previously added item...</option>
        @foreach($grouped as $cat => $list)
          <optgroup label="{{ $cat }}">
            @foreach($list as $it)
              <option value="{{ $it['id'] }}" {{ (string) old('item_id') === (string) $it['id'] ? 'selected' : '' }}>{{ $it['name'] }}{{ $it['brand'] ? ' - '.$it['brand'] : '' }} ({{ $it['code'] }}) &middot; {{ $it['total'] }} in stock</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
      <div class="text-[11px] text-gray-400 mt-1">Item not in the list? <a href="{{ route('admin.inventory.consumable.create') }}" class="text-gold underline">Add it once as a new item</a>.</div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
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
      <div id="priceBox">
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Selling price <span id="curLbl" class="normal-case text-gray-400"></span></label>
        <input type="number" step="0.01" min="0" name="price_local" id="priceIn" value="{{ old('price_local') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
        <div id="priceHint" class="text-[11px] text-gray-400 mt-1"></div>
      </div>
      <div>
        <label class="block text-xs font-700 text-gray-500 uppercase mb-1">Supplier invoice / lot (optional)</label>
        <input type="text" name="supplier_ref" maxlength="80" value="{{ old('supplier_ref') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
      </div>
    </div>

    <button class="bg-gold text-navy font-display font-700 text-sm px-6 py-2.5 rounded-xl hover:bg-yellow-400 transition-colors">Import batch</button>
    <a href="{{ route('admin.inventory.consumable.index') }}" class="text-xs text-gray-500 ml-3 underline">Back to consumables</a>
  </form>

  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
    <div class="text-xs font-700 text-gray-500 uppercase mb-2">Stock now</div>
    <div id="stockBox" class="text-sm text-gray-400">Select an item to see its quantity at every location.</div>
  </div>
</div>

<script>
const ITEMS = @json($flat->keyBy('id'));
const sym = {NGN:'₦', GHS:'GH₵', USD:'$'};
const itemSel = document.getElementById('itemSel'), locSel = document.getElementById('locSel');
function refresh() {
  const it = ITEMS[itemSel.value], box = document.getElementById('stockBox');
  const hint = document.getElementById('priceHint'), inp = document.getElementById('priceIn'), cur = document.getElementById('curLbl');
  if (!it) { box.textContent = 'Select an item to see its quantity at every location.'; hint.textContent = ''; return; }
  let h = '<div class="font-700 text-navy mb-1">' + it.name + (it.brand ? ' - ' + it.brand : '') + '</div><div class="text-[11px] text-gray-400 mb-2">' + it.code + '</div>';
  Object.entries(it.locations).forEach(([l, q]) => { h += '<div class="flex justify-between text-xs py-0.5"><span>' + l + '</span><b>' + q + '</b></div>'; });
  h += '<div class="flex justify-between text-sm border-t border-gray-100 mt-2 pt-2"><span>Total</span><b>' + it.total + '</b></div>';
  box.innerHTML = h;
  const p = it.prices[locSel.value];
  if (locSel.value && p) { hint.textContent = 'Already stocked here - price stays as it is (' + (sym[p.cur] || '') + p.price.toLocaleString() + '). Leave blank.'; cur.textContent = ''; inp.required = false; inp.placeholder = 'not needed'; }
  else if (locSel.value) { hint.textContent = 'First time at this location - enter its selling price.'; inp.required = true; inp.placeholder = ''; }
  else { hint.textContent = ''; inp.required = false; }
}
itemSel.addEventListener('change', refresh); locSel.addEventListener('change', refresh); refresh();
</script>
@endsection
