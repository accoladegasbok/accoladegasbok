{{-- FILE: resources/views/admin/tickets/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Staff Tickets')
@section('page-title', 'Staff Tickets')
@section('page-sub', 'Internal staff requests and approvals')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5 text-sm text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="flex items-center justify-between mb-5">
    <div class="flex gap-2">
        <a href="{{ route('admin.tickets.index') }}"
           class="text-xs px-3 py-1.5 rounded-lg {{ !request('status') ? 'bg-navy text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            All
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'pending']) }}"
           class="text-xs px-3 py-1.5 rounded-lg {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            Pending
            @if($pendingCount > 0)
            <span class="ml-1 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'approved']) }}"
           class="text-xs px-3 py-1.5 rounded-lg {{ request('status') === 'approved' ? 'bg-green-600 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            Approved
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'rejected']) }}"
           class="text-xs px-3 py-1.5 rounded-lg {{ request('status') === 'rejected' ? 'bg-red-600 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            Rejected
        </a>
    </div>
    <a href="{{ route('admin.tickets.create') }}"
       class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
        + New Ticket
    </a>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <table class="w-full">
        <thead class="bg-navy text-white">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Ticket #</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Subject</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Category</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Raised By</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Date</th>
                <th class="px-4 py-3 text-xs font-display uppercase tracking-wide">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tickets as $t)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gold font-700">{{ $t->ticket_no }}</td>
                <td class="px-4 py-3">
                    <div class="font-600 text-sm text-navy">{{ $t->subject }}</div>
                    @if($t->description)
                    <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($t->description, 60) }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-600">{{ $t->category }}</td>
                <td class="px-4 py-3 text-xs text-gray-600">{{ $t->raised_by_name ?? 'Unknown' }}</td>
                <td class="px-4 py-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                        {{ $t->status === 'approved'  ? 'bg-green-100 text-green-700'  :
                           ($t->status === 'rejected'  ? 'bg-red-100 text-red-700'      :
                           ($t->status === 'completed' ? 'bg-blue-100 text-blue-700'    :
                                                         'bg-amber-100 text-amber-700')) }}">
                        {{ ucfirst($t->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">
                    {{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.tickets.show', $t->id) }}"
                           class="text-xs border border-gray-200 rounded-lg px-3 py-1 hover:border-navy hover:text-navy transition-colors">
                            View
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                    No tickets yet.
                    <a href="{{ route('admin.tickets.create') }}" class="text-gold underline ml-1">Create the first one</a>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $tickets->links() }}
    </div>
</div>

@endsection
