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
  <input type="text" id="invSearchQ" value="{{ request('q') }}" placeholder="Part name, code, ref, room, bin, category, displacement..."
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
  <select id="invSearchLocation" onchange="onInventoryLocationChange()" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white focus:outline-none">
    <option value="">All locations</option>
    @foreach($locations as $l)<option value="{{ $l }}" {{ request('location')===$l?'selected':'' }}>{{ $l }}</option>@endforeach
  </select>
  {{-- NEW: Room filter — scoped to whichever location is currently
       selected. Options refresh via AJAX on Location change (reuses the
       already-existing admin.audit.rooms-for-location endpoint) since
       the live-search swap below only replaces the table, not this bar. --}}
  <select id="invSearchRoom" onchange="liveInventorySearch()" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body bg-white focus:outline-none">
    <option value="">All rooms</option>
    @foreach($rooms as $r)<option value="{{ $r->id }}" {{ (string) request('room')===(string) $r->id ?'selected':'' }}>{{ $r->name }}</option>@endforeach
  </select>
  <span id="invSearchSpinner" class="hidden self-center text-xs text-gray-400 font-body">Searching...</span>
  @if(request()->hasAny(['q','brand','category','location','status']))
    <a href="{{ route('admin.inventory.index') }}" class="border border-gray-200 text-gray-500 font-body text-xs px-4 py-2.5 rounded-xl hover:bg-gray-50">Clear</a>
  @endif
</div>

{{-- NEW: Bulk action bar — hidden until at least one row is checked.
     Currently powers multi-barcode printing (item A of this request);
     designed so future bulk actions (status change, delete, etc.) can
     reuse the same selected-ids form. --}}
<div id="bulkActionBar" class="hidden items-center gap-3 bg-navy text-white rounded-xl px-4 py-3 mb-4">
  <span id="bulkSelectedCount" class="text-xs font-body font-500">0 selected</span>
  <form id="bulkBarcodeForm" method="POST" action="{{ route('admin.inventory.barcodes.bulk') }}" target="_blank" class="inline">
    @csrf
    <div id="bulkBarcodeIdsContainer"></div>
    <button type="submit" class="bg-gold text-navy font-display font-700 text-xs px-4 py-1.5 rounded-lg hover:bg-yellow-400 transition-colors">
      🏷 Print Barcodes
    </button>
  </form>
  <button type="button" onclick="clearBulkSelection()" class="text-xs font-body text-slate-300 hover:text-white ml-auto">
    Clear selection
  </button>
</div>

{{-- Table --}}
<div id="invResultsContainer">
<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr>
          <th class="px-4 py-3">
            <input type="checkbox" id="selectAllRows" onchange="toggleSelectAllRows(this)" class="rounded border-gray-300">
          </th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Vehicle</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Grade</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Price</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Qty</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Room</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($parts as $p)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3">
            <input type="checkbox" class="row-checkbox rounded border-gray-300" value="{{ $p->id }}" onchange="updateBulkSelection()">
          </td>
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $p->part_name }}
              @if($p->side && $p->side !== 'N/A')
                <span class="text-xs text-gray-400">· {{ $p->side }}</span>
              @endif
            </div>
            {{-- Stock number now clearly shows Our Reference (source_ref)
                 as its own labelled badge — this is a 6-char code (e.g.
                 FEM001) that staff treat as critical, so it's no longer
                 just a faint trailing slash-separated value. --}}
            <div class="text-xs text-gray-400 font-mono mt-0.5">
              {{ $p->part_code }}
              @if(!empty($p->source_ref))
                <span class="inline-flex items-center bg-gold/10 text-gold font-700 px-1.5 py-0.5 rounded ml-1">Ref: {{ $p->source_ref }}</span>
              @endif
            </div>
          </td>
          <td class="px-4 py-3">
            <span class="badge {{ $p->part_category === 'Consumable' ? 'badge-amber' : 'badge-gray' }}">
              {{ $p->part_category }}
            </span>
          </td>
          <td class="px-4 py-3">
            @if($p->part_category === 'Consumable')
              @php
                // Custom-typed brands (e.g. "INFINITY") are stored as
                // brand='Generic' with the real name folded into the
                // start of part_name as "Name - Product...". Recover
                // the real name for display here rather than showing
                // the generic placeholder.
                $displayBrand = $p->brand;
                if ($p->brand === 'Generic' && str_contains($p->part_name, ' - ')) {
                    $displayBrand = explode(' - ', $p->part_name, 2)[0];
                }
              @endphp
              <div class="font-500 text-navy text-xs">{{ $displayBrand }}</div>
              <div class="text-xs text-gray-400">{{ $p->unit_size ?? 'Universal' }}</div>
            @else
              <div class="font-500 text-navy text-xs">{{ $p->brand }} {{ $p->model }}</div>
              <div class="text-xs text-gray-400">{{ $p->year_from }}@if($p->year_to != $p->year_from)–{{ $p->year_to }}@endif</div>
              {{-- NEW: engine displacement, shown whenever recorded —
                   most relevant for Engine-category rows, but harmless
                   to show anywhere it's set. --}}
              @if(!empty($p->engine_displacement))
                <div class="text-xs text-gray-400">{{ $p->engine_displacement }}</div>
              @endif
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
          <td class="px-4 py-3">
            <span class="font-display font-700 text-navy text-sm">{{ $p->stock_qty ?? 1 }}</span>
          </td>
          <td class="px-4 py-3 text-xs text-gray-600">{{ $p->location }}</td>
          <td class="px-4 py-3 text-xs text-gray-600">
            {{ $p->room_name ?? '—' }}
            @if(!empty($p->full_bin_code))
              <div class="text-[10px] text-gray-400 font-mono">{{ $p->full_bin_code }}</div>
            @endif
          </td>
          <td class="px-4 py-3">
            {{-- Status dropdown — now includes ALL 7 real statuses.
                 Previously only had Available/Reserved/Sold, so staff
                 could FILTER by Hold/Core/Scrapped above but never
                 actually SET a part to those statuses — this was the
                 actual gap in improvement #1. --}}
            {{-- data-stock-qty / data-original-status feed the qty-sold
                 prompt in updateStatus() below — needed so selling 2 of
                 20 units only depletes 2, instead of marking the whole
                 batch Sold (item E fix). --}}
            <select onchange="updateStatus({{ $p->id }}, this.value, {{ (int) ($p->stock_qty ?? 1) }}, this)"
              data-original-status="{{ $p->status }}"
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
        <tr><td colspan="11" class="px-4 py-12 text-center text-gray-400 text-sm font-body">No parts found.</td></tr>
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

// NEW: when Location changes, refresh the Room dropdown's options via
// the existing rooms-for-location endpoint (built for the Audit page,
// reused here) before running the normal search — otherwise Room would
// keep showing rooms from whatever location was selected on page load.
async function onInventoryLocationChange() {
    const location = document.getElementById('invSearchLocation').value;
    const roomSelect = document.getElementById('invSearchRoom');
    const previousValue = roomSelect.value;

    try {
        const res = await fetch(`/admin/audit/rooms-for-location?location=${encodeURIComponent(location)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        roomSelect.innerHTML = '<option value="">All rooms</option>';
        (data.rooms || []).forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            roomSelect.appendChild(opt);
        });

        // Keep the previous room selected if it still exists for the
        // new location; otherwise it naturally falls back to "All rooms".
        if ([...roomSelect.options].some(o => o.value === previousValue)) {
            roomSelect.value = previousValue;
        }
    } catch (e) {
        console.error('Could not refresh room options', e);
    }

    liveInventorySearch();
}

async function doLiveInventorySearch() {
    const spinner = document.getElementById('invSearchSpinner');
    try {
        const url = new URL(window.location.href);
        const q = document.getElementById('invSearchQ').value;
        const brand = document.getElementById('invSearchBrand').value;
        const category = document.getElementById('invSearchCategory').value;
        const location = document.getElementById('invSearchLocation').value;
        const room = document.getElementById('invSearchRoom').value;

        url.searchParams.set('q', q);
        url.searchParams.set('brand', brand);
        url.searchParams.set('category', category);
        url.searchParams.set('location', location);
        url.searchParams.set('room', room);
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

        // Row checkboxes just got replaced along with the table — any
        // prior selection no longer maps to real DOM elements, so reset
        // the bulk action bar to avoid a stale/confusing state.
        clearBulkSelection();
    } catch (e) {
        console.error('Live inventory search failed', e);
    } finally {
        spinner.classList.add('hidden');
    }
}

// NEW: multi-select + bulk barcode printing (item A of this request).
function toggleSelectAllRows(headerCheckbox) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = headerCheckbox.checked);
    updateBulkSelection();
}

function updateBulkSelection() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const bar = document.getElementById('bulkActionBar');
    const countLabel = document.getElementById('bulkSelectedCount');
    const idsContainer = document.getElementById('bulkBarcodeIdsContainer');

    if (checked.length > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }

    countLabel.textContent = `${checked.length} selected`;

    // Rebuild hidden ids[] inputs for the bulk barcode form
    idsContainer.innerHTML = '';
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        idsContainer.appendChild(input);
    });
}

function clearBulkSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAllRows');
    if (selectAll) selectAll.checked = false;
    updateBulkSelection();
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

// FIXED (item E): previously sent {status} only — the backend then
// blindly flipped the ENTIRE row to Sold no matter how many units
// were actually sold. Now, for multi-unit rows (stock_qty > 1) being
// marked Sold, this prompts for how many units were sold and sends
// qty_sold along with the request. The backend only marks the row
// Sold once stock_qty reaches 0 — a partial sale keeps it Available
// with the remaining quantity intact.
async function updateStatus(id, status, currentQty, selectEl) {
  let qtySold = null;

  if (status === 'Sold' && currentQty > 1) {
    const input = prompt(`How many units are being sold? (${currentQty} currently in stock)`, currentQty);

    if (input === null) {
      // Cancelled — revert the dropdown so it doesn't show "Sold"
      // when nothing actually changed.
      selectEl.value = selectEl.dataset.originalStatus;
      return;
    }

    qtySold = parseInt(input, 10);
    if (!qtySold || qtySold < 1 || qtySold > currentQty) {
      alert(`Enter a number between 1 and ${currentQty}.`);
      selectEl.value = selectEl.dataset.originalStatus;
      return;
    }
  }

  try {
    const res = await fetch(`/admin/inventory/${id}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ status, qty_sold: qtySold }),
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      alert(data.error || 'Could not update status.');
      selectEl.value = selectEl.dataset.originalStatus;
      return;
    }

    // Refresh the table in place so the Qty column and Status badge
    // both reflect the real remaining stock, rather than trusting
    // the optimistic dropdown value.
    if (typeof doLiveInventorySearch === 'function') {
      doLiveInventorySearch();
    } else {
      window.location.reload();
    }
  } catch (e) {
    console.error('Status update failed', e);
    alert('Could not update status — check your connection and try again.');
    selectEl.value = selectEl.dataset.originalStatus;
  }
}
</script>
@endpush
