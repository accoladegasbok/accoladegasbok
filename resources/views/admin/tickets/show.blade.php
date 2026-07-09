{{-- FILE: resources/views/admin/tickets/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Ticket ' . $ticket->ticket_no)
@section('page-title', $ticket->ticket_no)
@section('page-sub', $ticket->subject)

@section('content')
<div class="max-w-2xl">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Ticket Details --}}
    <div class="stat-card mb-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="font-mono text-gold font-700 text-sm">{{ $ticket->ticket_no }}</div>
                <div class="font-display font-700 text-navy text-lg mt-1">{{ $ticket->subject }}</div>
            </div>
            <span class="text-[10px] px-3 py-1.5 rounded-full font-700
                {{ $ticket->status === 'approved'  ? 'bg-green-100 text-green-700'  :
                   ($ticket->status === 'rejected'  ? 'bg-red-100 text-red-700'      :
                   ($ticket->status === 'completed' ? 'bg-blue-100 text-blue-700'    :
                                                      'bg-amber-100 text-amber-700')) }}">
                {{ ucfirst($ticket->status) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Category</div>
                <div class="text-gray-700">{{ $ticket->category }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Raised By</div>
                <div class="text-gray-700">{{ $ticket->raised_by_name ?? 'Unknown' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Date Submitted</div>
                <div class="text-gray-700">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y H:i') }}</div>
            </div>
            @if($ticket->reference_type && $ticket->reference_id)
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Reference</div>
                <div class="text-gray-700 font-mono">{{ ucfirst($ticket->reference_type) }}: {{ $ticket->reference_id }}</div>
            </div>
            @endif
        </div>

        @if($ticket->description)
        <div class="bg-gray-50 rounded-xl p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide mb-2">Details</div>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $ticket->description }}</div>
        </div>
        @endif
    </div>

    {{-- Resolution (if resolved) --}}
    @if($ticket->resolved_at)
    <div class="stat-card mb-5 {{ $ticket->status === 'approved' ? 'border-l-4 border-green-500' : 'border-l-4 border-red-400' }}">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-2">Resolution</div>
        <div class="flex items-center gap-2 mb-2">
            <span class="font-600 text-sm text-navy">{{ $ticket->resolved_by_name ?? 'Admin' }}</span>
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($ticket->resolved_at)->format('d M Y H:i') }}</span>
        </div>
        @if($ticket->resolution_notes)
        <div class="text-sm text-gray-700">{{ $ticket->resolution_notes }}</div>
        @endif
    </div>
    @endif

    {{-- Resolve form (admin/manager, pending tickets only) --}}
    @if($isApprover && $ticket->status === 'pending')
    <div class="stat-card">
        <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Resolve This Ticket</h3>
        <form method="POST" action="{{ route('admin.tickets.resolve', $ticket->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Decision *</label>
                <select name="status" required
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                    <option value="approved">Approved — action will be taken</option>
                    <option value="rejected">Rejected — cannot approve</option>
                    <option value="completed">Completed — already done</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Resolution Notes</label>
                <textarea name="resolution_notes" rows="3"
                          placeholder="Explain your decision or what action was taken..."
                          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">
                    Submit Resolution
                </button>
                <a href="{{ route('admin.tickets.index') }}"
                   class="border border-gray-200 text-gray-500 text-sm px-5 py-3 rounded-xl hover:bg-gray-50">
                    Back
                </a>
            </div>
        </form>
    </div>
    @else
    <a href="{{ route('admin.tickets.index') }}"
       class="inline-block border border-gray-200 text-gray-500 text-sm px-5 py-3 rounded-xl hover:bg-gray-50 transition-colors">
        ← Back to Tickets
    </a>
    @endif

</div>
@endsection
