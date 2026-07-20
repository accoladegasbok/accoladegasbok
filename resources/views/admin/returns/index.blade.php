{{-- FILE: resources/views/admin/returns/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Returns')
@section('page-title','Returns')
@section('page-sub','Logged returns awaiting inspection or already resolved')

@section('header-actions')
<a href="{{ route('admin.returns.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + Log a Return
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
  @foreach(['pending_inspection' => 'Pending Inspection', 'resolved' => 'Resolved', 'all' => 'All'] as $val => $lbl)
  <a href="{{ route('admin.returns.index', ['status' => $val]) }}"
     class="inline-flex items-center gap-1.5 text-xs font-body font-500 px-3 py-1.5 rounded-full border transition-colors
       {{ $status === $val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $lbl }}
    @if(isset($counts[$val]))<span class="font-mono">{{ $counts[$val] }}</span>@endif
  </a>
  @endforeach
</div>

<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Type</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Reason</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Refund</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Logged</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($returns as $r)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $r->part_name }}</div>
            <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $r->part_code }} · {{ $r->brand }} {{ $r->model }}</div>
          </td>
          <td class="px-4 py-3">
            <span class="badge {{ $r->return_type === 'customer' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($r->return_type) }}</span>
          </td>
          <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate">{{ $r->reason }}</td>
          <td class="px-4 py-3 text-xs font-mono text-gray-700">
            {{ $r->refund_amount_local ? '₦' . number_format($r->refund_amount_local, 2) : '—' }}
          </td>
          <td class="px-4 py-3">
            @if($r->status === 'pending_inspection')
              <span class="badge badge-amber">Pending Inspection</span>
            @else
              <span class="badge badge-green">Resolved — {{ str_replace('_',' ', ucfirst($r->resolution)) }}</span>
            @endif
          </td>
          <td class="px-4 py-3 text-xs text-gray-400">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</td>
          <td class="px-4 py-3 text-right">
            <a href="{{ route('admin.returns.show', $r->id) }}" class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
              {{ $r->status === 'pending_inspection' ? 'Inspect' : 'View' }}
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No returns in this view.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($returns->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $returns->links() }}</div>
  @endif
</div>

@endsection
