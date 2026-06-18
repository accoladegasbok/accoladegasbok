{{-- FILE: resources/views/admin/audit/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Audit Session')
@section('page-title', $session->location . ' — ' . $session->category)
@section('page-sub', 'Audit started by ' . $session->started_by . ' on ' . \Carbon\Carbon::parse($session->created_at)->format('M j, Y'))
@section('header-actions')
<a href="{{ route('admin.audit.index') }}"
   class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  ← Back to Audits
</a>
@endsection
@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl mb-5">
  {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-5">
  {{ session('error') }}
</div>
@endif

{{-- Summary bar --}}
<div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-5">
  <div>
    <span class="text-xs text-gray-400 uppercase tracking-wider">Status</span>
    <div class="font-display font-700 text-sm {{ $session->status === 'completed' ? 'text-green-600' : 'text-navy' }}">
      {{ $session->status === 'completed' ? 'Completed' : 'In Progress' }}
    </div>
  </div>
  <div>
    <span class="text-xs text-gray-400 uppercase tracking-wider">Total Items</span>
    <div class="font-display font-700 text-navy text-sm">{{ $items->count() }}</div>
  </div>
  <div>
    <span class="text-xs text-gray-400 uppercase tracking-wider">Counted</span>
    <div class="font-display font-700 text-navy text-sm" id="countedTotal">{{ $items->where('counted_qty', '!=', null)->count() }}</div>
  </div>
  <div>
    <span class="text-xs text-gray-400 uppercase tracking-wider">Discrepancies</span>
    <div class="font-display font-700 text-amber-600 text-sm" id="discrepancyTotal">{{ $items->where('discrepancy', '!=', 0)->where('counted_qty', '!=', null)->count() }}</div>
  </div>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-5">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Expected</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Counted</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Reason (if mismatch)</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
      <tr class="border-b border-gray-50" id="row-{{ $item->id }}">
        <td class="px-4 py-3">
          <div class="font-500 text-navy text-xs">{{ $item->part_name }}</div>
          <div class="text-xs text-gray-400 font-mono">{{ $item->part_code }}</div>
        </td>
        <td class="px-4 py-3 font-mono text-gray-600 text-sm">{{ $item->expected_qty }}</td>
        <td class="px-4 py-3">
          <input type="number" min="0" id="counted-{{ $item->id }}" value="{{ $item->counted_qty }}"
            {{ $session->status === 'completed' ? 'disabled' : '' }}
            class="w-20 border border-gray-200 rounded-lg px-2 py-1.5 text-sm font-mono focus:outline-none focus:border-gold">
        </td>
        <td class="px-4 py-3">
          <input type="text" id="reason-{{ $item->id }}" value="{{ $item->reason }}" placeholder="e.g. damaged, miscount, sold not updated"
            {{ $session->status === 'completed' ? 'disabled' : '' }}
            class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-gold">
        </td>
        <td class="px-4 py-3 text-right">
          @if($session->status !== 'completed')
          <button type="button" onclick="saveCount({{ $item->id }})"
            class="bg-navy text-white text-xs font-500 px-3 py-1.5 rounded-lg hover:bg-opacity-90 transition-colors">
            Save
          </button>
          @endif
          <span id="status-{{ $item->id }}" class="ml-2 text-xs"></span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if($session->status !== 'completed')
<form method="POST" action="{{ route('admin.audit.complete', $session->id) }}" id="completeForm">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
    <label class="flex items-center gap-2 text-sm text-gray-600">
      <input type="checkbox" name="apply_adjustments" value="1" class="rounded">
      Adjust stock quantities in inventory to match counted amounts
    </label>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Complete Audit
    </button>
  </div>
</form>
@endif

@endsection

@push('scripts')
<script>
async function saveCount(itemId) {
    const counted = document.getElementById('counted-' + itemId).value;
    const reason  = document.getElementById('reason-' + itemId).value;
    const statusEl = document.getElementById('status-' + itemId);

    if (counted === '') {
        statusEl.textContent = 'Enter a count';
        statusEl.className = 'ml-2 text-xs text-red-500';
        return;
    }

    statusEl.textContent = 'Saving...';
    statusEl.className = 'ml-2 text-xs text-gray-400';

    try {
        const res = await fetch(`{{ route('admin.audit.count', $session->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value || ''
            },
            body: JSON.stringify({ item_id: itemId, counted_qty: counted, reason: reason })
        });
        const data = await res.json();

        if (!res.ok) {
            statusEl.textContent = data.error || 'Error saving';
            statusEl.className = 'ml-2 text-xs text-red-500';
            return;
        }

        statusEl.textContent = data.discrepancy === 0 ? 'Matched ✓' : `Diff: ${data.discrepancy > 0 ? '+' : ''}${data.discrepancy}`;
        statusEl.className = 'ml-2 text-xs ' + (data.discrepancy === 0 ? 'text-green-600' : 'text-amber-600');
    } catch (e) {
        statusEl.textContent = 'Network error';
        statusEl.className = 'ml-2 text-xs text-red-500';
    }
}
</script>
@endpush
