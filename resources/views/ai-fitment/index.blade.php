{{-- FILE: resources/views/admin/ai-fitment/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'AI Fitment Review')
@section('page-title', 'AI Fitment Review')
@section('page-sub', 'Tick suggestions, review them a second time, then approve. Nothing becomes fitment on one click.')

@section('header-actions')
<a href="{{ route('admin.ai-fitment.report') }}" class="text-xs font-body text-navy border border-gray-200 bg-white rounded-xl px-3 py-2 hover:border-navy whitespace-nowrap">30-day report</a>
@endsection

@php
  $tabs = ['pending' => 'To review', 'vehicle' => 'Vehicle-based', 'confirmed' => 'Approved', 'rejected' => 'Rejected'];
  $confBadge = ['high' => 'badge-green', 'medium' => 'badge-amber', 'low' => 'badge-red'];
@endphp

@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<div class="flex flex-wrap items-center gap-2 mb-4">
  @foreach($tabs as $key => $label)
  <a href="{{ route('admin.ai-fitment.index', ['tab' => $key]) }}"
     class="px-3.5 py-1.5 rounded-full text-xs font-display font-700 tracking-wide border {{ $tab === $key ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $label }} <span class="opacity-70">({{ $counts[$key] }})</span>
  </a>
  @endforeach
  <form method="GET" class="ml-auto flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="confidence" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
      <option value="">Any confidence</option>
      @foreach(['high','medium','low'] as $c)<option value="{{ $c }}" {{ $conf === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>@endforeach
    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="Part, make or model..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:border-gold">
  </form>
</div>

@if($tab === 'vehicle')
<div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 mb-4 text-xs font-body">
  These came from the Compatibility Checker, so they are not tied to one part. To approve one, open the Compatibility Checker and add it to the right group there. You can reject the ones that are wrong from here.
</div>
@endif

<form method="POST" id="queueForm">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm font-body">
      <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
        <tr>
          @if(in_array($tab, ['pending','vehicle']))<th class="px-3 py-3 w-10"><input type="checkbox" id="checkAll" aria-label="Select all"></th>@endif
          <th class="text-left px-3 py-3">Part</th>
          <th class="text-left px-3 py-3">AI says it also fits</th>
          <th class="text-left px-3 py-3">Confidence</th>
          <th class="text-left px-3 py-3">Why</th>
          <th class="text-left px-3 py-3">{{ in_array($tab, ['confirmed','rejected']) ? 'Decided' : 'Suggested' }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
        <tr class="border-t border-gray-100 align-top">
          @if(in_array($tab, ['pending','vehicle']))<td class="px-3 py-3"><input type="checkbox" name="ids[]" value="{{ $r->id }}" class="row-check"></td>@endif
          <td class="px-3 py-3">
            @if($r->part_id)
              <div class="font-700 text-navy">{{ $r->part_name }}</div>
              <div class="text-xs text-gray-400 font-mono">{{ $r->part_code }}</div>
              <div class="text-xs text-gray-500">{{ $r->part_brand }} {{ $r->part_model }} {{ $r->part_year_from }}{{ $r->part_year_to && $r->part_year_to != $r->part_year_from ? '–' . $r->part_year_to : '' }}</div>
            @else
              <span class="text-xs text-gray-400">Vehicle-based</span>
            @endif
          </td>
          <td class="px-3 py-3 font-500 text-navy">{{ $r->suggested_make }} {{ $r->suggested_model }}<div class="text-xs text-gray-500">{{ $r->suggested_year_from }}{{ $r->suggested_year_to != $r->suggested_year_from ? '–' . $r->suggested_year_to : '' }}</div></td>
          <td class="px-3 py-3"><span class="badge {{ $confBadge[$r->confidence] ?? 'badge-gray' }}">{{ ucfirst($r->confidence) }}</span></td>
          <td class="px-3 py-3 text-xs text-gray-600 max-w-md">{{ $r->reason }}@if($r->review_note)<div class="text-gray-400 mt-1">Note: {{ $r->review_note }}</div>@endif</td>
          <td class="px-3 py-3 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse(in_array($tab, ['confirmed','rejected']) ? ($r->reviewed_at ?? $r->updated_at) : $r->created_at)->format('d M Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Nothing here.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(in_array($tab, ['pending','vehicle']) && $rows->count())
  <div class="sticky bottom-0 mt-3 bg-white border border-gray-200 rounded-2xl shadow-lg p-3 flex flex-wrap items-center gap-3">
    <span class="text-sm font-body text-gray-600"><span id="selCount">0</span> selected (max {{ $maxBatch }} to review at a time)</span>
    @if($tab === 'pending')
    <button type="submit" formaction="{{ route('admin.ai-fitment.review') }}" class="bg-navy text-white font-display font-700 text-sm px-5 py-2.5 rounded-xl">Review selected →</button>
    @endif
    <input type="text" name="reason" placeholder="Reason, to reject" class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-56">
    <button type="submit" formaction="{{ route('admin.ai-fitment.reject') }}" class="border border-red-300 text-red-600 font-display font-700 text-sm px-5 py-2.5 rounded-xl" onclick="return confirm('Reject the selected suggestions? This is logged.')">Reject selected</button>
  </div>
  @endif
</form>
<div class="mt-4">{{ $rows->links() }}</div>

<script>
(function () {
    var boxes = function () { return document.querySelectorAll('.row-check'); };
    function count() { var n = 0; boxes().forEach(function (b) { if (b.checked) n++; }); var el = document.getElementById('selCount'); if (el) el.textContent = n; }
    document.addEventListener('change', function (e) {
        if (e.target.id === 'checkAll') { boxes().forEach(function (b) { b.checked = e.target.checked; }); }
        if (e.target.classList && (e.target.classList.contains('row-check') || e.target.id === 'checkAll')) count();
    });
})();
</script>
@endsection
