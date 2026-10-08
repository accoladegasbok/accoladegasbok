{{-- FILE: resources/views/admin/ai-fitment/review.blade.php
     The SECOND step. Each suggestion is shown again, with warnings, and must be ticked one by one. --}}
@extends('admin.layouts.admin')
@section('title', 'Review AI fitment')
@section('page-title', 'Review before approving')
@section('page-sub', 'Look at each one again. Approved fitment shows to customers and decides what we promise will fit.')

@php $lvl = ['high' => 'bg-red-50 border-red-200 text-red-700', 'mid' => 'bg-amber-50 border-amber-200 text-amber-800', 'info' => 'bg-blue-50 border-blue-200 text-blue-800']; @endphp

@section('content')
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif

<a href="{{ route('admin.ai-fitment.index') }}" class="text-xs text-gray-400 hover:text-navy">← Back to the list (nothing has been saved)</a>

<form method="POST" action="{{ route('admin.ai-fitment.confirm') }}" id="confirmForm" class="mt-4">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">

  <div class="space-y-3">
    @foreach($items as $s)
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
      <div class="flex flex-wrap justify-between gap-3">
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider">This part</div>
          <div class="font-display font-700 text-navy">{{ $s->part_name }} <span class="font-mono text-xs text-gray-400">{{ $s->part_code }}</span></div>
          <div class="text-xs text-gray-500">{{ $s->part_brand }} {{ $s->part_model }} {{ $s->part_year_from }}{{ $s->part_year_to && $s->part_year_to != $s->part_year_from ? '–' . $s->part_year_to : '' }}</div>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-400 uppercase tracking-wider">AI says it also fits</div>
          <div class="font-display font-700 text-navy">{{ $s->suggested_make }} {{ $s->suggested_model }} {{ $s->suggested_year_from }}{{ $s->suggested_year_to != $s->suggested_year_from ? '–' . $s->suggested_year_to : '' }}</div>
          <span class="badge {{ ['high' => 'badge-green', 'medium' => 'badge-amber', 'low' => 'badge-red'][$s->confidence] ?? 'badge-gray' }}">{{ ucfirst($s->confidence) }} confidence</span>
        </div>
      </div>
      <p class="text-sm text-gray-600 font-body mt-3">{{ $s->reason }}</p>
      @foreach($s->warnings as $w)
      <div class="mt-2 border rounded-lg px-3 py-2 text-xs font-body {{ $lvl[$w['level']] ?? $lvl['info'] }}">{{ $w['text'] }}</div>
      @endforeach
      <label class="mt-3 flex items-start gap-2 text-sm font-body text-navy font-500">
        <input type="checkbox" name="verified[{{ $s->id }}]" value="1" class="verify-box mt-1">
        <span>I checked this one{{ $s->confidence !== 'high' ? ' — against an OEM number, a catalogue or a physical fit' : '' }}.</span>
      </label>
    </div>
    @endforeach
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mt-4">
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Note for the log (optional)</label>
    <input type="text" name="note" maxlength="500" placeholder="e.g. Checked against the Toyota catalogue" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
    <div class="flex flex-wrap items-center gap-3 mt-4">
      <button type="submit" id="confirmBtn" disabled class="bg-gold text-navy font-display font-700 text-sm px-6 py-3 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed">Approve <span id="okCount">0</span> of {{ $items->count() }}</button>
      <span class="text-xs text-gray-400">The button unlocks when every box above is ticked. Approvals are logged with your name.</span>
    </div>
  </div>
</form>

<script>
(function () {
    var boxes = document.querySelectorAll('.verify-box'), btn = document.getElementById('confirmBtn'), cnt = document.getElementById('okCount');
    function check() { var n = 0; boxes.forEach(function (b) { if (b.checked) n++; }); cnt.textContent = n; btn.disabled = (n !== boxes.length); }
    boxes.forEach(function (b) { b.addEventListener('change', check); });
    check();
})();
</script>
@endsection
