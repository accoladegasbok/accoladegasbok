{{-- FILE: resources/views/admin/part-approvals/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Awaiting Approval')
@section('page-title', 'Awaiting Approval')
@section('page-sub', 'New stock and restocks stay off the website until a manager or admin approves them.')

@php
  $tabs = ['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];
@endphp

@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<div class="flex flex-wrap items-center gap-2 mb-4">
  @foreach($tabs as $key => $label)
  <a href="{{ route('admin.part-approvals.index', ['tab' => $key]) }}"
     class="px-3.5 py-1.5 rounded-full text-xs font-display font-700 tracking-wide border {{ $tab === $key ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $label }} <span class="opacity-70">({{ $counts[$key] }})</span>
  </a>
  @endforeach
</div>

<form method="POST" action="{{ route('admin.part-approvals.approve') }}" id="approveForm">
@csrf
@if($tab === 'pending' && $rows->count())
<div class="flex flex-wrap items-center gap-3 mb-3">
  <label class="text-xs font-body text-gray-600 flex items-center gap-1.5"><input type="checkbox" id="selAll" class="accent-gold"> Select all on this page</label>
  <button type="submit" onclick="return confirm('Approve every ticked item and put it live on the website?')"
          class="bg-navy text-white text-xs font-display font-700 tracking-wide rounded-xl px-4 py-2 hover:bg-navy-light">Approve ticked</button>
  <span class="text-xs text-gray-400 font-body">Open an item with Edit first if anything looks wrong.</span>
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
<table class="w-full text-sm font-body">
  <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
    <tr>
      @if($tab === 'pending')<th class="px-3 py-2 w-8"></th>@endif
      <th class="px-3 py-2 text-left">Photo</th>
      <th class="px-3 py-2 text-left">Item</th>
      <th class="px-3 py-2 text-left">Vehicle</th>
      <th class="px-3 py-2 text-left">Grade / Price</th>
      <th class="px-3 py-2 text-left">Where</th>
      <th class="px-3 py-2 text-left">Entered by</th>
      <th class="px-3 py-2 text-right"></th>
    </tr>
  </thead>
  <tbody>
  @forelse($rows as $r)
    @php
      $photos = json_decode($r->photos ?? '[]', true) ?: [];
      $sym = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'][$r->currency_code ?? 'USD'] ?? '';
      $price = ($r->currency_code ?? '') === 'NGN' ? number_format($r->price_local) : number_format($r->price_local, 2);
      $yr = $r->year_from ? ($r->year_to && $r->year_to != $r->year_from ? $r->year_from . '–' . $r->year_to : $r->year_from) : '';
    @endphp
    <tr class="border-t border-gray-100 align-top">
      @if($tab === 'pending')<td class="px-3 py-3"><input type="checkbox" name="ids[]" value="{{ $r->id }}" class="rowSel accent-gold"></td>@endif
      <td class="px-3 py-3">
        @if(!empty($photos[0]))
          <img src="{{ asset(config('media.prefix') . '/' . $photos[0]) }}" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
        @else
          <div class="w-16 h-16 rounded-lg bg-gray-100 text-[10px] text-gray-400 flex items-center justify-center text-center">No photo</div>
        @endif
      </td>
      <td class="px-3 py-3">
        <div class="font-700 text-navy">{{ $r->part_name ?? '(removed)' }}</div>
        <div class="text-xs text-gray-500">{{ $r->part_code }} · {{ $r->part_category }}</div>
        @if($r->type === 'restock')
          <span class="inline-block mt-1 text-[11px] px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-700">RESTOCK +{{ $r->qty_to_add }} units (now {{ $r->stock_qty }})</span>
        @else
          <span class="inline-block mt-1 text-[11px] px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-700">NEW ITEM · qty {{ $r->stock_qty }}</span>
        @endif
      </td>
      <td class="px-3 py-3 text-xs text-gray-700">{{ strtoupper(trim(($r->brand ?? '') . ' ' . ($r->model ?? ''))) }}<br>{{ $yr }}</td>
      <td class="px-3 py-3 text-xs">Grade {{ $r->condition_grade }}<br><strong>{{ $sym }}{{ $price }}</strong></td>
      <td class="px-3 py-3 text-xs text-gray-600">{{ $r->location }}<br>{{ $r->bin_location }}</td>
      <td class="px-3 py-3 text-xs text-gray-600">{{ $r->requested_by_name ?? '—' }}<br>{{ \Carbon\Carbon::parse($r->created_at)->format('d M, g:ia') }}
        @if($tab !== 'pending' && $r->reviewed_by_name)<br><span class="text-gray-400">{{ ucfirst($r->status) }} by {{ $r->reviewed_by_name }}</span>@endif
        @if($r->status === 'rejected' && $r->note)<br><span class="text-red-500">“{{ $r->note }}”</span>@endif
      </td>
      <td class="px-3 py-3 text-right whitespace-nowrap">
        @if($r->part_name)<a href="{{ route('admin.inventory.edit', $r->part_id) }}" class="text-xs text-navy underline mr-2">Edit</a>@endif
        @if($tab === 'pending')
          <button type="submit" form="oneApprove{{ $r->id }}" class="text-xs bg-green-600 text-white rounded-lg px-3 py-1.5 hover:bg-green-700">Approve</button>
          <button type="button" onclick="rejectItem({{ $r->id }})" class="text-xs text-red-600 border border-red-200 rounded-lg px-3 py-1.5 hover:bg-red-50 ml-1">Reject</button>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="8" class="px-3 py-10 text-center text-gray-400">Nothing here.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
</form>

{{-- One tiny form per row so each Approve / Reject button works on its own. --}}
@foreach($rows as $r)
  @if($tab === 'pending')
  <form id="oneApprove{{ $r->id }}" method="POST" action="{{ route('admin.part-approvals.approve') }}" class="hidden">@csrf<input type="hidden" name="ids[]" value="{{ $r->id }}"></form>
  @endif
@endforeach
<form id="rejectForm" method="POST" class="hidden">@csrf<input type="hidden" name="reason" id="rejectReason"></form>

<div class="mt-4">{{ $rows->links() }}</div>

<script>
document.getElementById('selAll')?.addEventListener('change', function () {
  document.querySelectorAll('.rowSel').forEach(function (c) { c.checked = this.checked; }, this);
});
function rejectItem(id) {
  var reason = prompt('Why is this being rejected? (the person who entered it will see this)');
  if (!reason || !reason.trim()) return;
  var f = document.getElementById('rejectForm');
  f.action = '{{ url('/admin/part-approvals') }}/' + id + '/reject';
  document.getElementById('rejectReason').value = reason.trim();
  f.submit();
}
</script>
@endsection
