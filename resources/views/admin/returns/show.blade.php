{{-- FILE: resources/views/admin/returns/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Return Detail')
@section('page-title','Return — ' . $return->part_code)
@section('page-sub', $return->part_name . ' · ' . $return->brand . ' ' . $return->model)

@section('content')
<div class="max-w-2xl">

  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
  @endif
  @if(session('error'))
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
  @endif

  {{-- ── Return details (read-only) ──────────────────────────────── --}}
  <div class="stat-card mb-5">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Return Details</h2>
    <div class="grid grid-cols-2 gap-4 text-sm font-body mb-4">
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Type</div><div class="font-500 text-navy">{{ ucfirst($return->return_type) }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Logged</div><div class="font-500 text-navy">{{ \Carbon\Carbon::parse($return->created_at)->format('d M Y, H:i') }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Logged By</div><div class="font-500 text-navy">{{ $createdBy ?? '—' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Part Location</div><div class="font-500 text-navy">{{ $return->location }}</div></div>
    </div>
    @if($invoice)
    <div class="bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 mb-3 text-sm font-body text-blue-800">
      Linked to invoice <strong>{{ $invoice->invoice_no }}</strong> — {{ $invoice->customer_name }}
    </div>
    @endif
    <div class="bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-body text-gray-700">
      <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reason</div>
      {{ $return->reason }}
    </div>
  </div>

  @if($return->status === 'resolved')
  {{-- ── Already resolved — show outcome ──────────────────────────── --}}
  <div class="stat-card bg-green-50 border-green-200">
    <h2 class="font-display font-700 text-green-800 text-sm tracking-wide uppercase mb-2">Resolved</h2>
    <p class="text-sm font-body text-green-700">
      Resolution: <strong>{{ str_replace('_',' ', ucfirst($return->resolution)) }}</strong><br>
      By: {{ $resolvedBy ?? '—' }} on {{ \Carbon\Carbon::parse($return->resolved_at)->format('d M Y, H:i') }}
    </p>
    @if($return->resolution_notes)
      <p class="text-sm font-body text-green-700 mt-2">{{ $return->resolution_notes }}</p>
    @endif
  </div>
  @else
  {{-- ── Resolution form ──────────────────────────────────────────── --}}
  <div class="stat-card">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Resolve This Return</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Part is currently on <strong class="text-amber-600">Hold</strong>. Choose what happens to it.</p>

    <form method="POST" action="{{ route('admin.returns.resolve', $return->id) }}" id="resolveForm">
      @csrf

      <div class="grid grid-cols-3 gap-3 mb-4">
        <label class="cursor-pointer">
          <input type="radio" name="resolution" value="restock_good" class="sr-only peer" required>
          <div class="border-2 border-gray-200 rounded-xl p-3 text-center peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Restock — Good</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Back to Available</div>
          </div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="resolution" value="core" class="sr-only peer">
          <div class="border-2 border-gray-200 rounded-xl p-3 text-center peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Core / Bad</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">For rebuild</div>
          </div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="resolution" value="scrapped" class="sr-only peer">
          <div class="border-2 border-gray-200 rounded-xl p-3 text-center peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Scrap</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Dispose</div>
          </div>
        </label>
      </div>

      {{-- Bin assignment — hidden for Scrap --}}
      <div id="binSection" class="mb-4" style="display:none;">
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Assign Bin</label>
        <div class="grid grid-cols-2 gap-3">
          <select id="storeRoomSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Loading rooms for {{ $return->location }}...</option>
          </select>
          <select id="binSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select Store Room first</option>
          </select>
        </div>
        <input type="hidden" name="storage_shelf_id" id="storageShelfIdInput">
      </div>

      <div class="mb-4">
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Notes (optional)</label>
        <textarea name="resolution_notes" rows="2" placeholder="Any details about the inspection or resolution..."
          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"></textarea>
      </div>

      <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Confirm Resolution
      </button>
    </form>
  </div>
  @endif

</div>

@push('scripts')
<script>
const PART_LOCATION = @json($return->location ?? '');

document.querySelectorAll('input[name="resolution"]').forEach(input => {
    input.addEventListener('change', function() {
        const binSection = document.getElementById('binSection');
        if (this.value === 'scrapped') {
            binSection.style.display = 'none';
        } else {
            binSection.style.display = '';
            if (!document.getElementById('storeRoomSelect').dataset.loaded) {
                loadStoreRooms();
            }
        }
    });
});

async function loadStoreRooms() {
    const roomSelect = document.getElementById('storeRoomSelect');
    roomSelect.dataset.loaded = '1';
    try {
        const res = await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(PART_LOCATION)}`);
        const data = await res.json();
        if (!data.rooms || data.rooms.length === 0) {
            roomSelect.innerHTML = '<option value="">No store rooms set up for this location yet</option>';
            return;
        }
        roomSelect.innerHTML = '<option value="">Select Store Room</option>' +
            data.rooms.map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    } catch (e) {
        roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
    }
}

document.getElementById('storeRoomSelect').addEventListener('change', async function() {
    const binSelect = document.getElementById('binSelect');
    if (!this.value) {
        binSelect.innerHTML = '<option value="">Select Store Room first</option>';
        return;
    }
    binSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res = await fetch(`/admin/storage/shelves-for-room?room_id=${this.value}`);
        const data = await res.json();
        binSelect.innerHTML = '<option value="">Select Bin (optional)</option>' +
            (data.shelves || []).map(s => `<option value="${s.id}">${s.full_bin_code}</option>`).join('');
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
});

document.getElementById('binSelect').addEventListener('change', function() {
    document.getElementById('storageShelfIdInput').value = this.value;
});
</script>
@endpush
@endsection
