{{-- FILE: resources/views/admin/inventory/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Inventory')
@section('page-title','All Inventory')
@section('page-sub','Browse, filter and manage all parts in stock')

@section('header-actions')
<div class="flex gap-2">
  <a href="{{ route('admin.inventory.consumable.create') }}"
     class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
    + Add Consumable
  </a>
  <a href="{{ route('admin.inventory.manual-add') }}"
     class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-opacity-90 transition-colors">
    + Add Part Manually
  </a>
  <a href="{{ route('admin.inventory.create') }}"
     class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    + Add via Harvest
  </a>
</div>
@endsection

@section('content')

{{-- Status tabs — now includes Missing (was previously the only one
     missing from this filter row; Hold/Core/Scrapped were already here
     but had no way to actually BE set on a part — see the per-row
     dropdown below, which is the real fix for improvement #1). --}}
<div class="flex flex-wrap gap-2 mb-5">
  @foreach([''=> 'All Parts', 'Available'=>'Available', 'Reserved'=>'Reserved', 'Sold'=>'Sold', 'Missing'=>'Missing', 'Hold'=>'Hold', 'Core'=>'Core', 'Scrapped'=>'Scrapped'] as $val => $lbl)
  <a href="{{ request()->fullUrlWithQuery(['status'=>$val,'page'=>1]) }}"
     class="inline-flex items-center gap-1.5 text-xs font-body font-500 px-3 py-1.5 rounded-full border transition-colors
       {{ request('status')===$val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $lbl }}
    @if($val && isset($counts[$val]))
      <span class="font-mono">{{ $counts[$val] }}</span>
    @endif
  </a>
  @endforeach
</div>

{{-- Filters — all real-time, no Enter key or Search button needed --}}
<div class="flex flex-wrap gap-2 mb-5">
  <input type="text" id="invSearchQ" value="{{ request('q') }}" placeholder="Part name, code, model, OEM..."
    oninput="liveInventorySearch()"
    class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
  <select id="invSearchBrand" onchange="liveInventorySearch()" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white focus:outline-none">
    <option value="">All brands</option>
    @foreach($brands as $b)<option value="{{ $b }}" {{ request('brand')===$b?'selected':'' }}>{{ $b }}</option>@endforeach
  </select>
  <select id="invSearchCategory" onchange="liveInventorySearch()" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white focus:outline-none">
    <option value="">All categories</option>
    @foreach($categories as $c)<option value="{{ $c }}" {{ request('category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
  </select>
  <select id="invSearchLocation" onchange="liveInventorySearch()" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white focus:outline-none">
    <option value="">All locations</option>
    @foreach($locations as $l)<option value="{{ $l }}" {{ request('location')===$l?'selected':'' }}>{{ $l }}</option>@endforeach
  </select>
  <span id="invSearchSpinner" class="hidden self-center text-xs text-gray-400 font-body">Searching...</span>
  @if(request()->hasAny(['q','brand','category','location','status']))
    <a href="{{ route('admin.inventory.index') }}" class="border border-gray-200 text-gray-500 font-body text-xs px-4 py-2.5 rounded-xl hover:bg-gray-50">Clear</a>
  @endif
</div>

{{-- Table --}}
<div id="invResultsContainer">
<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Vehicle</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Grade</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Price</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($parts as $p)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $p->part_name }}
              @if($p->side && $p->side !== 'N/A')
                <span class="text-xs text-gray-400">· {{ $p->side }}</span>
              @endif
            </div>
            <div class="text-xs text-gray-400 font-mono mt-0.5">
              {{ $p->part_code }}@if(!empty($p->source_ref))<span class="text-gray-300"> / </span><span class="text-gold">{{ $p->source_ref }}</span>@endif
            </div>
          </td>
          <td class="px-4 py-3">
            <span class="badge {{ $p->part_category === 'Consumable' ? 'badge-amber' : 'badge-gray' }}">
              {{ $p->part_category }}
            </span>
          </td>
          <td class="px-4 py-3">
            @if($p->part_category === 'Consumable')
              <div class="font-500 text-navy text-xs">{{ $p->brand }}</div>
              <div class="text-xs text-gray-400">{{ $p->unit_size ?? 'Universal' }}</div>
            @else
              <div class="font-500 text-navy text-xs">{{ $p->brand }} {{ $p->model }}</div>
              <div class="text-xs text-gray-400">{{ $p->year_from }}@if($p->year_to != $p->year_from)–{{ $p->year_to }}@endif</div>
            @endif
          </td>
          <td class="px-4 py-3">
            <span class="badge {{ $p->condition_grade==='A'?'badge-green':($p->condition_grade==='B'?'badge-blue':($p->condition_grade==='New'?'badge-gray':'badge-amber')) }}">
              {{ $p->condition_grade }}
            </span>
          </td>
          <td class="px-4 py-3">
            @php
              // ── FIXED PRICE — this part's own currency, no live conversion ──
              $priceLocal = $p->price_local ?? $p->price_usd; // fallback for pre-migration rows
              $currencyCode = $p->currency_code ?? 'USD';
              $symbol = match($currencyCode) { 'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$' };
              $decimals = $currencyCode === 'NGN' ? 0 : 2;
            @endphp
            <div class="font-display font-700 text-navy text-sm">{{ $symbol }}{{ number_format($priceLocal, $decimals) }}</div>
            <div class="text-xs text-gray-400">{{ $currencyCode }}</div>
          </td>
          <td class="px-4 py-3 text-xs text-gray-600">{{ $p->location }}</td>
          <td class="px-4 py-3">
            {{-- Status dropdown — now includes ALL 7 real statuses.
                 Previously only had Available/Reserved/Sold, so staff
                 could FILTER by Hold/Core/Scrapped above but never
                 actually SET a part to those statuses — this was the
                 actual gap in improvement #1. --}}
            <select onchange="updateStatus({{ $p->id }}, this.value)"
              class="border border-gray-200 rounded-lg px-2 py-1 text-xs font-body bg-white focus:outline-none">
              @foreach(['Available','Reserved','Sold','Missing','Hold','Core','Scrapped'] as $s)
                <option value="{{ $s }}" {{ $p->status===$s?'selected':'' }}>{{ $s }}</option>
              @endforeach
            </select>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <a href="{{ route('admin.inventory.edit', $p->id) }}"
                 class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
                Edit
              </a>
              <a href="{{ route('admin.inventory.barcode', $p->id) }}" target="_blank"
                 class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
                🏷 Barcode
              </a>
              @if(in_array(session('staff_role'), ['admin','manager']))
              <form method="POST" action="{{ route('admin.inventory.destroy', $p->id) }}"
                    onsubmit="return confirm('Delete this part? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs font-body border border-red-200 text-red-400 hover:border-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg transition-colors">
                  Delete
                </button>
              </form>
              @else
              <button type="button" onclick="deletePartWithPin({{ $p->id }})"
                class="text-xs font-body border border-red-200 text-red-400 hover:border-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg transition-colors">
                Delete
              </button>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm font-body">No parts found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($parts->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $parts->links() }}</div>
  @endif
</div>
</div>{{-- /#invResultsContainer --}}

<script>
let invSearchTimer = null;

function liveInventorySearch() {
    clearTimeout(invSearchTimer);
    document.getElementById('invSearchSpinner').classList.remove('hidden');
    invSearchTimer = setTimeout(doLiveInventorySearch, 350);
}

async function doLiveInventorySearch() {
    const spinner = document.getElementById('invSearchSpinner');
    try {
        const url = new URL(window.location.href);
        const q = document.getElementById('invSearchQ').value;
        const brand = document.getElementById('invSearchBrand').value;
        const category = document.getElementById('invSearchCategory').value;
        const location = document.getElementById('invSearchLocation').value;

        url.searchParams.set('q', q);
        url.searchParams.set('brand', brand);
        url.searchParams.set('category', category);
        url.searchParams.set('location', location);
        url.searchParams.delete('page');

        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newResults = doc.getElementById('invResultsContainer');
        if (newResults) {
            document.getElementById('invResultsContainer').innerHTML = newResults.innerHTML;
        }

        window.history.replaceState({}, '', url.toString());
    } catch (e) {
        console.error('Live inventory search failed', e);
    } finally {
        spinner.classList.add('hidden');
    }
}

// ── Staff/Supervisor delete a part only via Supervisor-or-above PIN
// approval. Admin/Manager use the direct form above instead.
function deletePartWithPin(partId) {
    if (!confirm('Delete this part? This cannot be undone.')) return;
    requestOverride('delete_inventory_part', `Delete part #${partId}`, function(approvedBy, role) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/inventory/${partId}`;
        form.innerHTML = `
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="override_token" value="${approvedBy} (${role})">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}
</script>

@include('admin.override.modal-snippet')

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
async function updateStatus(id, status) {
  await fetch(`/admin/inventory/${id}/status`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ status }),
  });
}
</script>
@endpush
