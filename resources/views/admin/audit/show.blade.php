{{-- FILE: resources/views/admin/audit/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Audit — ' . $session->location)
@section('page-title', 'Counting: ' . $session->location)
@section('page-sub', ($session->room_name ? $session->room_name . ' · ' : '') . $session->category . ' · Started by ' . $session->started_by)

@section('header-actions')
<a href="{{ route('admin.audit.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  ← All Audits
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

{{-- FIXED: this whole page previously WAS just the scan-block fragment
     below, with no @extends/layout at all — meaning it never included
     the admin layout's <head>, and therefore never had the CSRF meta
     tag the scan JS depends on. That's exactly why scanning/typing
     silently failed with "network error" — the fetch() call was
     throwing before it could ever reach the network, since there was
     no CSRF token to read. Also, the manual-count table this comment
     assumed already existed elsewhere never actually did — added
     properly below. --}}

{{-- ── Scan to Count ─────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border-2 border-gold shadow-sm p-5 mb-5">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">📷 Scan to Count</h2>
  <p class="text-xs text-gray-400 font-body mb-3">Scan each physical part's barcode as you find it on the shelf — each scan adds 1 to that item's count automatically. You can also just type the code and press Enter.</p>
  <input type="text" id="scanInput" autocomplete="off" autofocus placeholder="Scan or type part barcode here..."
    class="w-full border-2 border-gold rounded-lg px-4 py-3 text-base font-mono focus:outline-none focus:ring-2 focus:ring-gold">
  <div id="scanFeedback" class="text-sm font-body mt-2 min-h-[20px]"></div>
</div>

{{-- ── Manual count table — every item in this audit session, with a
     fallback for entering a count by hand when scanning isn't
     practical (damaged label, awkward shelf position, etc). Uses the
     same recordCount() endpoint that already existed but had no UI. ── --}}
<div class="stat-card overflow-hidden p-0">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Items in This Audit</h2>
    <span class="text-xs text-gray-400 font-body">{{ $items->count() }} total · {{ $items->whereNotNull('counted_qty')->count() }} counted</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Code</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Expected</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Counted</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Discrepancy</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors" data-audit-item-id="{{ $item->id }}">
          <td class="px-4 py-3 font-500 text-navy">{{ $item->part_name }}</td>
          <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $item->part_code }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $item->expected_qty }}</td>
          <td class="px-4 py-3 font-700" data-counted-qty>{{ $item->counted_qty ?? '—' }}</td>
          <td class="px-4 py-3">
            @if($item->counted_qty === null)
              <span class="text-gray-300">—</span>
            @elseif($item->discrepancy == 0)
              <span class="badge badge-green">Match</span>
            @else
              <span class="badge badge-amber">{{ $item->discrepancy > 0 ? '+' : '' }}{{ $item->discrepancy }}</span>
            @endif
          </td>
          <td class="px-4 py-3 text-right">
            @if($session->status !== 'completed')
            <button type="button" onclick="toggleManualCount({{ $item->id }})"
              class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
              ✎ Enter Count
            </button>
            @endif
          </td>
        </tr>
        @if($session->status !== 'completed')
        <tr id="manual-count-row-{{ $item->id }}" class="hidden bg-blue-50 border-b border-gray-100">
          <td colspan="6" class="px-4 py-3">
            <form onsubmit="return submitManualCount(event, {{ $item->id }})" class="flex gap-2 items-end">
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Counted Qty</label>
                <input type="number" min="0" value="{{ $item->counted_qty }}" id="manual-qty-{{ $item->id }}" required
                  class="w-24 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-gold">
              </div>
              <div class="flex-1">
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Reason (required if count doesn't match expected)</label>
                <input type="text" id="manual-reason-{{ $item->id }}" placeholder="e.g. found extra in wrong bin, one damaged/scrapped..."
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-gold">
              </div>
              <button type="submit" class="bg-gold text-navy font-700 text-xs px-4 py-1.5 rounded-lg hover:bg-yellow-500 transition-colors">Save</button>
              <button type="button" onclick="toggleManualCount({{ $item->id }})" class="text-xs text-gray-400 px-2 hover:text-gray-600">Cancel</button>
            </form>
          </td>
        </tr>
        @endif
        @empty
        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No items in this audit.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($session->status !== 'completed')
<div class="mt-5 stat-card">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-2">Complete This Audit</h2>
  <p class="text-xs text-gray-400 font-body mb-3">Every item must have a count before this can be completed.</p>
  <form method="POST" action="{{ route('admin.audit.complete', $session->id) }}"
        onsubmit="return confirm('Complete this audit? This will finalize the discrepancy report.')">
    @csrf
    <label class="flex items-center gap-2 text-xs font-body text-gray-600 mb-3">
      <input type="checkbox" name="apply_adjustments" value="1">
      Also adjust system stock levels to match the counted quantities (recommended)
    </label>
    <button type="submit" class="bg-navy text-white font-display font-700 text-sm px-6 py-3 rounded-xl hover:bg-opacity-90 transition-colors">
      Complete Audit
    </button>
  </form>
</div>
@endif

<script>
const AUDIT_SESSION_ID = {{ $session->id }};
const scanInput = document.getElementById('scanInput');

scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleAuditScan(this.value.trim());
        this.value = '';
    }
});

async function handleAuditScan(code) {
    if (!code) return;
    const feedback = document.getElementById('scanFeedback');
    feedback.textContent = 'Looking up...';
    feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-gray-400';

    try {
        const res = await fetch(`/admin/audit/${AUDIT_SESSION_ID}/scan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ code }),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            feedback.textContent = '✕ ' + (data.error || 'Not found in this audit.');
            feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-red-600';
            return;
        }

        const diffText = data.discrepancy === 0 ? '' : (data.discrepancy > 0 ? ` (+${data.discrepancy} over expected)` : ` (${data.discrepancy} short of expected)`);
        feedback.textContent = `✓ ${data.part_name} — now counted: ${data.counted_qty} / expected ${data.expected_qty}${diffText}`;
        feedback.className = 'text-sm font-body mt-2 min-h-[20px] ' + (data.discrepancy === 0 ? 'text-green-600' : 'text-amber-600');

        const row = document.querySelector(`[data-audit-item-id="${data.item_id}"] [data-counted-qty]`);
        if (row) row.textContent = data.counted_qty;

    } catch (e) {
        feedback.textContent = 'Network error — try again.';
        feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-red-600';
    }
}

function toggleManualCount(itemId) {
    document.getElementById('manual-count-row-' + itemId).classList.toggle('hidden');
}

async function submitManualCount(event, itemId) {
    event.preventDefault();
    const qty = document.getElementById('manual-qty-' + itemId).value;
    const reason = document.getElementById('manual-reason-' + itemId).value;

    try {
        const res = await fetch(`/admin/audit/${AUDIT_SESSION_ID}/count`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ item_id: itemId, counted_qty: qty, reason: reason }),
        });
        const data = await res.json();
        if (!res.ok || data.error) {
            alert(data.error || 'Could not save this count.');
            return false;
        }
        location.reload(); // simplest reliable way to refresh discrepancy display
    } catch (e) {
        alert('Network error — try again.');
    }
    return false;
}
</script>

@endsection
