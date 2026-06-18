{{-- FILE: resources/views/admin/audit/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Inventory Audit')
@section('page-title','Inventory Audit')
@section('page-sub','Scheduled stock counts and discrepancy tracking')
@section('header-actions')
<a href="{{ route('admin.audit.create') }}"
   class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-opacity-90 transition-colors">
  + Start New Audit
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

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Started By</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Items</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Discrepancies</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($sessions as $s)
      <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 font-500 text-navy text-xs">{{ $s->location }}</td>
        <td class="px-4 py-3 text-gray-600 text-xs">{{ $s->category }}</td>
        <td class="px-4 py-3 text-gray-600 text-xs">{{ $s->started_by }}</td>
        <td class="px-4 py-3 text-gray-600 text-xs">{{ $s->total_items }}</td>
        <td class="px-4 py-3">
          @if($s->status === 'completed')
            <span class="badge {{ $s->discrepancy_items > 0 ? 'badge-amber' : 'badge-green' }}">
              {{ $s->discrepancy_items }}
            </span>
          @else
            <span class="text-gray-400 text-xs">—</span>
          @endif
        </td>
        <td class="px-4 py-3">
          <span class="badge {{ $s->status === 'completed' ? 'badge-green' : 'badge-blue' }}">
            {{ $s->status === 'completed' ? 'Completed' : 'In Progress' }}
          </span>
        </td>
        <td class="px-4 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($s->created_at)->format('M j, Y') }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.audit.show', $s->id) }}" class="text-gold hover:text-yellow-600 text-xs font-500">
            {{ $s->status === 'completed' ? 'View Report' : 'Continue Count' }} →
          </a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">
          No audit sessions yet. Start one to begin counting stock.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{ $sessions->links() }}

@endsection
