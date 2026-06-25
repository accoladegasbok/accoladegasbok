{{-- FILE: resources/views/admin/assets/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $asset->asset_tag)
@section('page-title', $asset->name)
@section('page-sub', $asset->asset_tag . ' · ' . $asset->category)

@section('header-actions')
<a href="{{ route('admin.assets.edit', $asset->id) }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  Edit
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

<div class="max-w-3xl space-y-5">

  <div class="stat-card">
    <div class="flex items-center justify-between mb-4">
      <span class="badge
        @if($asset->status==='In Service') badge-green
        @elseif($asset->status==='Serviceable') badge-blue
        @elseif($asset->status==='Needs Repair') badge-amber
        @else badge-red @endif">
        {{ $asset->status }}
      </span>
      <span class="text-xs text-gray-400 font-mono">{{ $asset->asset_tag }}</span>
    </div>
    <div class="grid grid-cols-2 gap-4 text-sm font-body">
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Location</div><div class="font-500 text-navy">{{ $asset->location }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Category</div><div class="font-500 text-navy">{{ $asset->category }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Serial Number</div><div class="font-500 text-navy">{{ $asset->serial_number ?? '—' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Assigned To</div><div class="font-500 text-navy">{{ $asset->assigned_to ?? '—' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Acquired</div><div class="font-500 text-navy">{{ $asset->acquired_date ? \Carbon\Carbon::parse($asset->acquired_date)->format('d M Y') : '—' }}{{ $asset->acquired_value ? ' · '.$asset->acquired_currency.' '.number_format($asset->acquired_value, 2) : '' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Last Serviced</div><div class="font-500 text-navy">{{ $asset->last_serviced_date ? \Carbon\Carbon::parse($asset->last_serviced_date)->format('d M Y') : '—' }}</div></div>
      @if($asset->next_service_due)
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Next Service Due</div>
        <div class="font-500 {{ \Carbon\Carbon::parse($asset->next_service_due)->isPast() ? 'text-red-600' : 'text-navy' }}">
          {{ \Carbon\Carbon::parse($asset->next_service_due)->format('d M Y') }}
          @if(\Carbon\Carbon::parse($asset->next_service_due)->isPast()) (OVERDUE) @endif
        </div>
      </div>
      @endif
    </div>
    @if($asset->notes)
    <div class="mt-3 bg-gray-50 rounded-lg p-3 text-sm font-body text-gray-600">{{ $asset->notes }}</div>
    @endif
  </div>

  <div class="stat-card">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">History</h2>
    @forelse($logs as $log)
    <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0 text-sm font-body">
      <span class="text-xs text-gray-400 w-24 flex-shrink-0">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</span>
      <span class="text-gray-600">
        @if($log->action === 'status_change') Status changed: <strong>{{ $log->from_value }}</strong> → <strong>{{ $log->to_value }}</strong>
        @elseif($log->action === 'location_change') Moved: <strong>{{ $log->from_value }}</strong> → <strong>{{ $log->to_value }}</strong>
        @elseif($log->action === 'serviced') Serviced on {{ $log->to_value }}
        @else {{ $log->note }}
        @endif
      </span>
    </div>
    @empty
    <p class="text-sm text-gray-400 font-body">No history yet.</p>
    @endforelse
  </div>

  <form method="POST" action="{{ route('admin.assets.destroy', $asset->id) }}" onsubmit="return confirm('Remove this asset from the register permanently?')">
    @csrf @method('DELETE')
    <button type="submit" class="text-xs font-body border border-red-200 text-red-500 hover:bg-red-50 px-4 py-2.5 rounded-xl transition-colors">
      Remove from Register
    </button>
  </form>

</div>
@endsection
