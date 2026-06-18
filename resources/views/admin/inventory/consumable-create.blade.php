{{-- FILE: resources/views/admin/inventory/consumable-create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Consumable')
@section('page-title','Add Consumable Item')
@section('page-sub','Oils, fluids, filters & additives — no vehicle fitment required')
@section('header-actions')
<a href="{{ route('admin.inventory.index') }}"
   class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  Cancel
</a>
@endsection
@section('content')

<form method="POST" action="{{ route('admin.inventory.consumable.store') }}" class="max-w-2xl">
  @csrf

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Product Details</h2>

    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="relative">
    <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Brand *</label>
    <input type="text" id="brandTypeahead" autocomplete="off" placeholder="Type or pick a brand..." required
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    <input type="hidden" name="brand" id="brandHidden">
    <input type="hidden" name="other_brand" id="otherBrandHidden">
    <div id="brandSuggestions" class="hidden absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-full mt-1 max-h-48 overflow-y-auto"></div>
</div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Size / Volume</label>
        <input type="text" name="unit_size" placeholder="e.g. 5L, 1 Quart, 4-pack"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>

    <div class="mb-4">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Product Name *</label>
      <input type="text" name="part_name" required placeholder="e.g. 5W-30 Synthetic Engine Oil"
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    </div>

    <div>
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatibility Note (optional)</label>
      <input type="text" name="compatibility_note" placeholder="e.g. Suitable for most 4-cylinder Toyota/Honda engines"
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Pricing & Condition</h2>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Price (USD) *</label>
        <input type="number" name="price_usd" step="0.01" min="0" required placeholder="0.00"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Condition *</label>
        <select name="condition_grade" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="New" selected>New</option>
          <option value="A">A — Like New</option>
          <option value="B">B — Good</option>
          <option value="C">C — Functional</option>
        </select>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Location & Stock</h2>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
        <select name="location" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach($locations as $loc)
            <option value="{{ $loc }}">{{ $loc }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Stock Quantity</label>
        <input type="number" name="stock_qty" value="1" min="1"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Notes</h2>
    <textarea name="description" rows="3" placeholder="Any additional notes about this item..."
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold"></textarea>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.inventory.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Add Consumable
    </button>
  </div>

</form>

<script>
const KNOWN_BRANDS = ['Mobil 1','Castrol','Valvoline','Shell','Fram','Bosch','Denso','NGK','ACDelco','Generic'];

const brandTypeahead   = document.getElementById('brandTypeahead');
const brandHidden      = document.getElementById('brandHidden');
const otherBrandHidden = document.getElementById('otherBrandHidden');
const brandSuggestions = document.getElementById('brandSuggestions');

function setBrandValue(typedValue) {
    const match = KNOWN_BRANDS.find(b => b.toLowerCase() === typedValue.toLowerCase());
    if (match) {
        brandHidden.value      = match;
        otherBrandHidden.value = '';
    } else if (typedValue.trim()) {
        // Not a known brand — fall back to Generic, keep real name for part_name
        brandHidden.value      = 'Generic';
        otherBrandHidden.value = typedValue.trim();
    } else {
        brandHidden.value      = '';
        otherBrandHidden.value = '';
    }
}

function renderBrandSuggestions(query) {
    const q = query.toLowerCase();
    const matches = KNOWN_BRANDS.filter(b => b.toLowerCase().includes(q));

    if (matches.length === 0) {
        brandSuggestions.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400 italic">No match — "${query}" will be saved as a new brand.</div>`;
    } else {
        brandSuggestions.innerHTML = matches.map(b =>
            `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm" onclick="selectBrand('${b}')">${b}</div>`
        ).join('');
    }
    brandSuggestions.classList.remove('hidden');
}

function selectBrand(brand) {
    brandTypeahead.value = brand;
    setBrandValue(brand);
    brandSuggestions.classList.add('hidden');
}

brandTypeahead.addEventListener('focus', function() {
    renderBrandSuggestions(this.value);
});

brandTypeahead.addEventListener('input', function() {
    renderBrandSuggestions(this.value);
    setBrandValue(this.value);
});

brandTypeahead.addEventListener('blur', function() {
    setTimeout(() => brandSuggestions.classList.add('hidden'), 150);
});
</script>

@endsection
