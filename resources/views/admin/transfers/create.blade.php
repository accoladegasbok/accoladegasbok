{{-- FILE: resources/views/admin/transfers/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','New Stock Transfer')
@section('page-title','New Stock Transfer')
@section('page-sub','Move parts between locations — they leave the sellable pool until marked Received')

@section('content')
<form method="POST" action="{{ route('admin.transfers.store') }}" id="transferForm">
@csrf
<div class="max-w-4xl space-y-5">

  @if($errors->any() || session('error'))
  <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body">
    {{ session('error') }}
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
  @endif

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Route</h2>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">From Location *</label>
        <select name="from_location" id="fromLocation" onchange="loadParts()" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option value="">Select origin</option>
          @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">To Location *</label>
        <select name="to_location" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option value="">Select destination</option>
          @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
        </select>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes (optional)</label>
        <input type="text" name="notes" placeholder="e.g. shipping carrier, tracking reference..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-navy">
      <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Select Parts to Transfer</h2>
      <p class="text-xs text-gray-400 mt-0.5">Pick a From Location above first</p>
    </div>
    <div class="p-5 border-b border-gray-100">
      <input type="text" id="searchInput" oninput="loadParts()" placeholder="Search part name, code, brand, model..." disabled
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      <div id="searchResults" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto"></div>
    </div>
    <div id="selectedContainer" class="p-4 space-y-2">
      <p class="text-xs text-gray-400 font-body text-center py-4" id="emptyMsg">No parts selected yet.</p>
    </div>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.transfers.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Create Transfer</button>
  </div>

</div>
</form>

<script>
let selected = {}; // part_id -> part object

let searchTimer = null;
function loadParts() {
    const loc = document.getElementById('fromLocation').value;
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = !loc;

    clearTimeout(searchTimer);
    if (!loc) { document.getElementById('searchResults').innerHTML = ''; return; }
    searchTimer = setTimeout(doSearch, 300);
}

async function doSearch() {
    const loc = document.getElementById('fromLocation').value;
    const q = document.getElementById('searchInput').value;
    const box = document.getElementById('searchResults');
    box.innerHTML = '<div class="text-xs text-gray-400 col-span-2">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.transfers.search-parts') }}?location=${encodeURIComponent(loc)}&q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.parts || data.parts.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 col-span-2">No matching parts at this location.</div>';
            return;
        }
        box.innerHTML = data.parts.map(p => `
            <div onclick='addPart(${JSON.stringify(p).replace(/'/g, "&#39;")})'
                class="border border-gray-200 rounded-lg p-2.5 cursor-pointer hover:border-gold transition-colors ${selected[p.id] ? 'opacity-40 pointer-events-none' : ''}">
                <div class="font-700 text-navy text-xs">${p.part_name}</div>
                <div class="text-xs text-gray-400">${p.brand} ${p.model} · ${p.part_code} · Grade ${p.condition_grade}</div>
            </div>`).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 col-span-2">Search failed.</div>';
    }
}

function addPart(part) {
    selected[part.id] = part;
    renderSelected();
    doSearch();
}
function removePart(id) {
    delete selected[id];
    renderSelected();
    doSearch();
}

function renderSelected() {
    const container = document.getElementById('selectedContainer');
    const ids = Object.keys(selected);
    if (ids.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-400 font-body text-center py-4">No parts selected yet.</p>';
        return;
    }
    container.innerHTML = ids.map(id => {
        const p = selected[id];
        return `
        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div>
                <div class="font-700 text-navy text-sm">${p.part_name}</div>
                <div class="text-xs text-gray-400">${p.part_code} · ${p.brand} ${p.model}</div>
                <input type="hidden" name="part_ids[]" value="${p.id}">
            </div>
            <button type="button" onclick="removePart(${p.id})" class="text-red-400 hover:text-red-600 text-xs">✕ Remove</button>
        </div>`;
    }).join('');
}

document.getElementById('transferForm').addEventListener('submit', function(e) {
    if (Object.keys(selected).length === 0) {
        e.preventDefault();
        alert('Select at least one part to transfer.');
    }
});
</script>
@endsection
