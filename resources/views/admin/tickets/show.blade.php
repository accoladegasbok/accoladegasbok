{{-- FILE: resources/views/admin/tickets/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $ticket->ticket_no)
@section('page-title', $ticket->ticket_no . ' — ' . $ticket->subject)
@section('page-sub', $ticket->category)

@section('content')
<div class="max-w-2xl space-y-5">

  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-body">{{ session('success') }}</div>
  @endif
  @if(session('error'))
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-body">{{ session('error') }}</div>
  @endif

  <div class="stat-card">
    <div class="flex items-center justify-between mb-3">
      <span class="badge
        @if($ticket->status==='pending') badge-amber
        @elseif($ticket->status==='approved' || $ticket->status==='completed') badge-green
        @else badge-red @endif">
        {{ ucfirst($ticket->status) }}
      </span>
      <span class="text-xs text-gray-400">Raised by {{ $ticket->raised_by_name }} · {{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, H:i') }}</span>
    </div>
    <p class="text-sm font-body text-gray-700 whitespace-pre-line">{{ $ticket->description ?: 'No description provided.' }}</p>

    @if($ticket->status !== 'pending')
    <div class="mt-4 pt-4 border-t border-gray-100">
      <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Resolution</div>
      <p class="text-sm text-gray-600">{{ $ticket->resolution_notes ?: '—' }}</p>
      <div class="text-xs text-gray-400 mt-2">By {{ $ticket->resolved_by_name }} on {{ \Carbon\Carbon::parse($ticket->resolved_at)->format('d M Y, H:i') }}</div>
    </div>
    @endif
  </div>

  @if($isApprover && $ticket->status === 'pending')
  <div class="stat-card">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Resolve This Ticket</h2>
    <form method="POST" action="{{ route('admin.tickets.resolve', $ticket->id) }}">
      @csrf
      <textarea name="resolution_notes" rows="3" placeholder="Notes (optional)..."
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm mb-3 focus:outline-none focus:border-gold"></textarea>
      <div class="flex gap-2">
        <button type="submit" name="status" value="approved" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-display font-700 text-sm py-2.5 rounded-xl transition-colors">Approve</button>
        <button type="submit" name="status" value="completed" class="flex-1 bg-navy text-white font-display font-700 text-sm py-2.5 rounded-xl hover:bg-navy-light transition-colors">Mark Completed</button>
        <button type="submit" name="status" value="rejected" class="flex-1 border border-red-200 text-red-500 hover:bg-red-50 font-body font-500 text-sm py-2.5 rounded-xl transition-colors">Reject</button>
      </div>
    </form>
  </div>
  @endif

</div>
@endsection
