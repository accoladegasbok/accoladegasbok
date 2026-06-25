{{-- FILE: resources/views/admin/transfers/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Stock Transfers')
@section('page-title','Stock Transfers')
@section('page-sub','Move parts between locations with a full paper trail')

@section('header-actions')
<a href="{{ route('admin.transfers.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + New Transfer
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

<div class="flex flex-wrap gap-2 mb-5">
  @foreach(['' => 'All', 'pending' => 'Pending', 'in_transit' => 'In Transit', 'received' => 'Received', 'cancelled' => 'Cancelled'] as $val => $lbl)
  <a href="{{ route('admin.transfers.index', ['status' => $val]) }}"
     class="inline-flex items-center gap-1.5 text-xs font-body font-500 px-3 py-1.5 rounded-full border transition-colors
       {{ request('status', '') === $val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $lbl }}
    @if($val && isset($counts[$val]))<span class="font-mono">{{ $counts[$val] }}</span>@endif
  </a>
  @endforeach
</div>

<div class="stat-card overflow-hidden p-0">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="border-b border-gray-100 bg-gray-50">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Transfer #</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Route</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Shipped</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($transfers as $t)
      <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 font-mono font-700 text-navy">{{ $t->transfer_no }}</td>
        <td class="px-4 py-3 text-gray-600">{{ $t->from_location }} → {{ $t->to_location }}</td>
        <td class="px-4 py-3">
          <span class="badge
            @if($t->status==='received') badge-green
            @elseif($t->status==='in_transit') badge-blue
            @elseif($t->status==='cancelled') badge-red
            @else badge-amber @endif">
            {{ str_replace('_',' ', ucfirst($t->status)) }}
          </span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-400">{{ $t->shipped_at ? \Carbon\Carbon::parse($t->shipped_at)->format('d M Y') : '—' }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.transfers.show', $t->id) }}" class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">View</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400 text-sm">No transfers yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($transfers->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $transfers->links() }}</div>
  @endif
</div>

@endsection
