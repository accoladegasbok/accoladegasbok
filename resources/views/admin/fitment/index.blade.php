{{-- FILE: resources/views/admin/fitment/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Fitment')
@section('page-title', 'Fitment')
@section('page-sub', 'Verify gearbox pin counts once, with the donor VIN and a connector photo. Review staged fitment lists.')

@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<div class="flex flex-wrap gap-2 mb-5">
  <a href="{{ route('admin.fitment.index', ['tab' => 'pins']) }}" class="px-4 py-2 rounded-full text-sm font-display font-700 tracking-wide border {{ $tab === 'pins' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">Pin counts</a>
  <a href="{{ route('admin.fitment.index', ['tab' => 'staging']) }}" class="px-4 py-2 rounded-full text-sm font-display font-700 tracking-wide border {{ $tab === 'staging' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">Staged fitment lists</a>
</div>

@if($tab === 'pins')
  @if(!$pinsReady)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm font-body">Run <code>php artisan migrate --force</code> first. The pin records table does not exist yet.</div>
  @else

  {{-- ── Record a pin count ───────────────────────────────────────────── --}}
  <div class="bg-white border border-gray-200 rounded-2xl p-5 mb-6">
    <h2 class="font-display font-700 text-navy text-lg tracking-wide mb-1">Record a pin count</h2>
    <p class="text-sm font-body text-gray-500 mb-4">Count the terminals on the gearbox connector at the valve body, just above the selector switch. Take a clear photo of that connector. Enter the VIN of the donor vehicle it came from.</p>

    <form method="POST" action="{{ route('admin.fitment.pins.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3 text-sm font-body">
      @csrf
      <div class="md:col-span-3">
        <label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Donor VIN</label>
        <div class="flex gap-2">
          <input type="text" name="donor_vin" id="pinVin" maxlength="17" value="{{ old('donor_vin') }}" required class="flex-1 border border-gray-200 rounded-lg px-3 py-2 font-mono uppercase" placeholder="17 characters">
          <button type="button" id="pinDecode" class="border border-navy text-navy rounded-lg px-3 py-2 hover:bg-navy hover:text-white">Fill from VIN</button>
        </div>
        <div id="pinDecodeMsg" class="text-xs mt-1 text-gray-500"></div>
      </div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Make</label><input type="text" name="make" id="pinMake" value="{{ old('make') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Model</label><input type="text" name="model" id="pinModel" value="{{ old('model') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></div>
      <div class="grid grid-cols-2 gap-2">
        <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Year from</label><input type="number" name="year_from" id="pinYf" value="{{ old('year_from') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></div>
        <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Year to</label><input type="number" name="year_to" id="pinYt" value="{{ old('year_to') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></div>
      </div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Engine code</label><input type="text" name="engine_code" id="pinEng" value="{{ old('engine_code') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 font-mono uppercase" placeholder="2AZ-FE"></div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Gearbox code</label><input type="text" name="transmission_code" id="pinTrans" value="{{ old('transmission_code') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 font-mono uppercase" placeholder="U250E"></div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Pin count (counted)</label><input type="number" name="pin_count" id="pinCount" value="{{ old('pin_count') }}" required min="1" max="40" class="w-full border border-gray-200 rounded-lg px-3 py-2 font-mono"></div>
      <div><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Stock number (optional)</label><input type="text" name="part_code" value="{{ old('part_code') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 font-mono"></div>
      <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Connector photo</label><input type="file" name="photo" accept="image/*" capture="environment" required class="w-full border border-gray-200 rounded-lg px-3 py-1.5 bg-white"></div>
      <div class="md:col-span-4"><label class="block text-xs text-gray-500 mb-1 uppercase tracking-wider">Notes</label><input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" class="w-full border border-gray-200 rounded-lg px-3 py-2" placeholder="Anything that helps the next person"></div>
      <div class="md:col-span-6 flex flex-wrap items-center gap-4">
        @if($canVerify)
        <label class="text-xs text-gray-600 flex items-center gap-1.5"><input type="checkbox" name="verify_now" value="1" class="accent-gold"> I checked this myself, verify it now</label>
        @endif
        <button class="bg-navy text-white font-display font-700 tracking-wide rounded-lg px-5 py-2">Save pin count</button>
      </div>
    </form>
    <div id="pinSuggest" class="hidden mt-3 text-xs font-body bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2"></div>
  </div>

  {{-- ── Records ───────────────────────────────────────────────────────── --}}
  <div class="flex flex-wrap gap-2 mb-3">
    @foreach(['pending' => 'Awaiting verification', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $k => $label)
      <a href="{{ route('admin.fitment.index', ['tab' => 'pins', 'status' => $k]) }}" class="px-3 py-1.5 rounded-full text-xs font-display font-700 border {{ $pinStatus === $k ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">{{ $label }} ({{ $pinCounts[$k] }})</a>
    @endforeach
  </div>

  <div class="bg-white border border-gray-200 rounded-2xl overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr>
        <th class="text-left px-3 py-2">Photo</th><th class="text-left px-3 py-2">Vehicle</th><th class="text-left px-3 py-2">Engine / Gearbox</th>
        <th class="text-left px-3 py-2">Pins</th><th class="text-left px-3 py-2">Donor VIN</th><th class="text-left px-3 py-2">By</th><th class="px-3 py-2"></th></tr></thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($pins as $p)
        <tr>
          <td class="px-3 py-2"><a href="{{ asset('storage/' . $p->photo_path) }}" target="_blank"><img src="{{ asset('storage/' . $p->photo_path) }}" class="w-16 h-12 object-cover rounded border border-gray-200" alt="Connector"></a></td>
          <td class="px-3 py-2 text-navy font-500">{{ $p->make }} {{ $p->model }}<div class="text-xs text-gray-400">{{ $p->year_from }}{{ $p->year_to != $p->year_from ? '–' . $p->year_to : '' }}</div></td>
          <td class="px-3 py-2 font-mono text-xs">{{ $p->engine_code ?: '—' }}<br>{{ $p->transmission_code ?: '—' }}</td>
          <td class="px-3 py-2 font-mono font-700 text-navy">{{ $p->pin_count }}-pin</td>
          <td class="px-3 py-2 font-mono text-xs">{{ $p->donor_vin }}</td>
          <td class="px-3 py-2 text-xs text-gray-500">{{ $p->submitted_by_name }}<br>{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('M j, Y') }}
            @if($p->status === 'verified')<div class="text-green-700">Verified by {{ $p->verified_by_name }}</div>@endif
            @if($p->status === 'rejected')<div class="text-red-700">{{ $p->reject_reason }}</div>@endif
            @if($p->notes)<div class="text-gray-400">{{ $p->notes }}</div>@endif
          </td>
          <td class="px-3 py-2 text-right whitespace-nowrap">
            @if($canVerify && $p->status !== 'verified')
              <form method="POST" action="{{ route('admin.fitment.pins.verify', $p->id) }}" class="inline">@csrf<button class="text-xs bg-green-600 text-white rounded px-2.5 py-1">Verify</button></form>
            @endif
            @if($canVerify && $p->status === 'pending')
              <form method="POST" action="{{ route('admin.fitment.pins.reject', $p->id) }}" class="inline" onsubmit="var r=prompt('Why is this rejected?'); if(!r) return false; this.reason.value=r;">@csrf<input type="hidden" name="reason"><button class="text-xs border border-red-300 text-red-600 rounded px-2.5 py-1">Reject</button></form>
            @endif
            @if(session('staff_role') === 'admin')
              <form method="POST" action="{{ route('admin.fitment.pins.delete', $p->id) }}" class="inline" onsubmit="return confirm('Delete this record and its photo?')">@csrf @method('DELETE')<button class="text-xs text-gray-400 hover:text-red-600 px-1.5">Delete</button></form>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Nothing here.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-3">{{ $pins->links() }}</div>
  @endif

@else
  {{-- ── Staged fitment lists ─────────────────────────────────────────── --}}
  @if(!$stagingReady)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm font-body">Run <code>php artisan migrate --force</code> first. The staging table does not exist yet.</div>
  @else
  <p class="text-sm font-body text-gray-500 mb-3">These lists are leads, not proof. Nothing on this page is shown to customers. Approving a row means "checked, worth using". It does not make it a verified fit.</p>
  <div class="flex flex-wrap gap-2 mb-3">
    @foreach(['staged' => 'To review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $label)
      <a href="{{ route('admin.fitment.index', ['tab' => 'staging', 'sstatus' => $k]) }}" class="px-3 py-1.5 rounded-full text-xs font-display font-700 border {{ $stageStatus === $k ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">{{ $label }} ({{ $stageCounts[$k] }})</a>
    @endforeach
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr>
        <th class="text-left px-3 py-2">Vehicle</th><th class="text-left px-3 py-2">Part</th><th class="text-left px-3 py-2">Engine / Gearbox</th><th class="text-left px-3 py-2">Level</th><th class="text-left px-3 py-2">Notes</th><th class="px-3 py-2"></th></tr></thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($staged as $s)
        <tr>
          <td class="px-3 py-2 text-navy font-500">{{ $s->make }} {{ $s->model }}<div class="text-xs text-gray-400">{{ $s->donor_years }}</div></td>
          <td class="px-3 py-2 text-xs">{{ $s->part_name }}</td>
          <td class="px-3 py-2 font-mono text-xs">{{ $s->engine_ref ?: '—' }}<br>{{ $s->transmission_ref ?: '—' }}@if($s->mount_group ?? null)<br><span class="text-gray-400">mount {{ $s->mount_group }}</span>@endif</td>
          <td class="px-3 py-2 text-xs">{{ $statusLabels[$s->fitment_status ?? ''] ?? 'Not assessed' }}</td>
          <td class="px-3 py-2 text-xs text-gray-500 max-w-xs">{{ $s->notes }}</td>
          <td class="px-3 py-2 text-right whitespace-nowrap">
            @if($canVerify)
              @if($s->status !== 'approved')<form method="POST" action="{{ route('admin.fitment.staging.decide', [$s->id, 'approved']) }}" class="inline">@csrf<button class="text-xs bg-green-600 text-white rounded px-2.5 py-1">Approve</button></form>@endif
              @if($s->status !== 'rejected')<form method="POST" action="{{ route('admin.fitment.staging.decide', [$s->id, 'rejected']) }}" class="inline">@csrf<button class="text-xs border border-red-300 text-red-600 rounded px-2.5 py-1">Reject</button></form>@endif
              @if($s->status !== 'staged')<form method="POST" action="{{ route('admin.fitment.staging.decide', [$s->id, 'staged']) }}" class="inline">@csrf<button class="text-xs text-gray-400 px-1.5">Undo</button></form>@endif
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">Nothing here.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-3">{{ $staged->links() }}</div>
  @endif
@endif

<script>
(function () {
  var btn = document.getElementById('pinDecode'); if (!btn) return;
  var msg = document.getElementById('pinDecodeMsg'), sug = document.getElementById('pinSuggest');
  function set(id, v) { var el = document.getElementById(id); if (el && v !== null && v !== undefined && v !== '') el.value = v; }
  btn.addEventListener('click', function () {
    var vin = document.getElementById('pinVin').value.trim().toUpperCase();
    msg.textContent = 'Looking up…'; sug.classList.add('hidden');
    fetch(@json(route('admin.fitment.decode-vin')) + '?vin=' + encodeURIComponent(vin), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (x) {
        if (!x.ok) { msg.textContent = x.j.error || 'Could not decode.'; return; }
        var d = x.j; msg.textContent = d.year + ' ' + d.make + ' ' + d.model;
        set('pinMake', d.make); set('pinModel', d.model);
        var yf = document.getElementById('pinYf'), yt = document.getElementById('pinYt');
        if (!yf.value) yf.value = d.year; if (!yt.value) yt.value = d.year;
        set('pinEng', d.suggest_engine_code); set('pinTrans', d.suggest_transmission_code);
        var lines = [];
        if (d.suggest_pin_count) lines.push('Our database suggests ' + d.suggest_pin_count + '-pin' + (d.pin_verified ? ' (already verified).' : ' (NOT verified). Count the real connector and enter that number.'));
        if (d.multiple_engines) lines.push('This vehicle came with more than one engine. Read the engine code off the unit.');
        if (d.note) lines.push(d.note);
        if (d.existing_record) lines.push('This VIN already has a record (' + d.existing_record.status + ', ' + d.existing_record.pin_count + '-pin).');
        if (lines.length) { sug.textContent = lines.join(' '); sug.classList.remove('hidden'); }
      })
      .catch(function () { msg.textContent = 'Could not reach the VIN service. Fill the fields by hand.'; });
  });
})();
</script>
@endsection
