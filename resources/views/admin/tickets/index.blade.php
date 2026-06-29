{{-- FILE: resources/views/admin/tickets/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Tickets')
@section('page-title', $isApprover ? 'Staff Tickets' : 'My Tickets')
@section('page-sub', $isApprover ? 'Requests raised by staff needing your approval/action' : 'Things you\'ve asked admin/manager to handle')

@section('header-actions')
<a href="{{ route('admin.tickets.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + Raise a Ticket
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

@if($isApprover)
<div class="flex gap-2 mb-4">
  @foreach(['' => 'All', 'pending' => "Pending ({$pendingCount})", 'approved' => 'Approved', 'rejected' => 'Rejected', 'completed' => 'Completed'] as $val => $lbl)
  <a href="{{ route('admin.tickets.index', ['status' => $val]) }}"
     class="text-xs font-body font-500 px-3 py-1.5 rounded-full border {{ request('status','')===$val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">
    {{ $lbl }}
  </a>
  @endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Ticket #</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Subject</th>
        @if($isApprover)<th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Raised By</th>@endif
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($tickets as $t)
      <tr class="border-b border-gray-50 hover:bg-gray-50 {{ $t->status==='pending' ? 'bg-amber-50' : '' }}">
        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $t->ticket_no }}</td>
        <td class="px-4 py-3 text-gray-600">{{ $t->category }}</td>
        <td class="px-4 py-3 font-700 text-navy">{{ $t->subject }}</td>
        @if($isApprover)<td class="px-4 py-3 text-gray-500">{{ $t->raised_by_name }}</td>@endif
        <td class="px-4 py-3">
          <span class="badge
            @if($t->status==='pending') badge-amber
            @elseif($t->status==='approved' || $t->status==='completed') badge-green
            @else badge-red @endif">
            {{ ucfirst($t->status) }}
          </span>
        </td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.tickets.show', $t->id) }}" class="text-xs font-body text-gold hover:text-yellow-600">View →</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No tickets.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($tickets->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $tickets->links() }}</div>
  @endif
</div>

@endsection
