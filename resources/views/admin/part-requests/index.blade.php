{{-- FILE: resources/views/admin/part-requests/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Part Requests')
@section('page-title', 'Part Requests')
@section('page-sub', 'Customers asking us to find a part — work each one from New to Closed')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>
@endif

{{-- Status tabs --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
  @php
    $tabs = ['open' => 'Open (New + Contacted)'] + $statuses + ['all' => 'All'];
  @endphp
  @foreach($tabs as $key => $label)
    @php
        $n = $key === 'open'
            ? (($counts['new'] ?? 0) + ($counts['contacted'] ?? 0))
            : ($key === 'all' ? $counts->sum() : ($counts[$key] ?? 0));
    @endphp
    <a href="{{ route('admin.part-requests.index', array_filter(['status' => $key, 'q' => $q])) }}"
       class="px-3.5 py-1.5 rounded-full text-xs font-display font-700 tracking-wide border {{ $status === $key ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
      {{ $label }} <span class="opacity-70">({{ $n }})</span>
    </a>
  @endforeach
  <form method="GET" class="ml-auto">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search name, phone, part, vehicle..."
           class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:border-gold">
  </form>
</div>

<div class="space-y-3">
  @forelse($requests as $r)
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <div class="font-display font-700 text-navy text-base">{{ $r->part_text }}</div>
        <div class="text-sm text-gray-600 font-body">{{ $r->vehicle_text ?: 'Vehicle not given' }}</div>
        @if($r->notes)<div class="text-xs text-gray-500 font-body mt-1">“{{ $r->notes }}”</div>@endif
        @if($r->part_id)
        <div class="text-xs text-gray-400 font-body mt-1">Looked at: <a class="underline" href="{{ route('admin.inventory.edit', $r->part_id) }}">{{ $r->part_code ?: ('part #' . $r->part_id) }}</a> {{ $r->looked_at_part ? '· ' . $r->looked_at_part : '' }}</div>
        @endif
      </div>
      <div class="text-right text-xs text-gray-500 font-body">
        <div class="font-700 text-navy text-sm">{{ $r->customer_name }}</div>
        <div><a class="underline" href="tel:{{ preg_replace('/[^0-9+]/', '', $r->customer_phone) }}">{{ $r->customer_phone }}</a></div>
        @if($r->customer_email)<div><a class="underline" href="mailto:{{ $r->customer_email }}">{{ $r->customer_email }}</a></div>@endif
        <div class="mt-1">{{ $r->region ?: '—' }} · {{ $r->source === 'part_page' ? 'from a part page' : 'from search' }}</div>
        <div>{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y, H:i') }}</div>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.part-requests.update', $r->id) }}" class="mt-4 flex flex-wrap items-end gap-3">
      @csrf
      <div>
        <label class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Status</label>
        <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          @foreach($statuses as $key => $label)
          <option value="{{ $key }}" {{ $r->status === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex-1 min-w-[220px]">
        <label class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Staff notes</label>
        <input type="text" name="staff_notes" value="{{ $r->staff_notes }}" maxlength="2000" placeholder="Who you called, what you found, price quoted..."
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-5 py-2 rounded-lg transition-colors">Save</button>
    </form>
    @if($r->handled_at)
    <div class="text-[11px] text-gray-400 font-body mt-2">Last updated {{ \Carbon\Carbon::parse($r->handled_at)->format('d M Y, H:i') }}</div>
    @endif
  </div>
  @empty
  <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-400 font-body">No requests here.</div>
  @endforelse
</div>

<div class="mt-5">{{ $requests->links() }}</div>

@endsection
