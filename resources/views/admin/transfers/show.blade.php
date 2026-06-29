{{-- FILE: resources/views/admin/transfers/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $transfer->transfer_no)
@section('page-title', $transfer->transfer_no)
@section('page-sub', $transfer->from_location . ' → ' . $transfer->to_location)

@section('header-actions')
<a href="{{ route('admin.transfers.waybill', $transfer->id) }}" target="_blank" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  🖨 View Waybill
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

<div class="max-w-3xl space-y-5">

  <div class="stat-card">
    <div class="flex items-center justify-between mb-4">
      <span class="badge
        @if($transfer->status==='received') badge-green
        @elseif($transfer->status==='in_transit') badge-blue
        @elseif($transfer->status==='cancelled') badge-red
        @else badge-amber @endif">
        {{ str_replace('_',' ', ucfirst($transfer->status)) }}
      </span>
      <span class="text-xs text-gray-400 font-mono">{{ $transfer->transfer_no }}</span>
    </div>
    <div class="grid grid-cols-2 gap-4 text-sm font-body">
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">From</div><div class="font-500 text-navy">{{ $transfer->from_location }}</div>
        @if($fromRooms->count())<div class="text-xs text-gray-400 mt-1">{{ $fromRooms->pluck('address')->filter()->implode(' · ') ?: 'No address on file for this location\'s rooms' }}</div>@endif
      </div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">To</div><div class="font-500 text-navy">{{ $transfer->to_location }}</div>
        @if($toRooms->count())<div class="text-xs text-gray-400 mt-1">{{ $toRooms->pluck('address')->filter()->implode(' · ') ?: 'No address on file for this location\'s rooms' }}</div>@endif
      </div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Created By</div><div class="font-500 text-navy">{{ $createdBy ?? '—' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Shipped</div><div class="font-500 text-navy">{{ $transfer->shipped_at ? \Carbon\Carbon::parse($transfer->shipped_at)->format('d M Y') : '—' }}</div></div>
      @if($transfer->status === 'received')
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Received By</div><div class="font-500 text-navy">{{ $receivedBy ?? '—' }}</div></div>
      <div><div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Received On</div><div class="font-500 text-navy">{{ \Carbon\Carbon::parse($transfer->received_at)->format('d M Y') }}</div></div>
      @endif
    </div>
    @if($transfer->notes)
    <div class="mt-3 bg-gray-50 rounded-lg p-3 text-sm font-body text-gray-600">{{ $transfer->notes }}</div>
    @endif
  </div>

  <form method="POST" action="{{ route('admin.transfers.receive', $transfer->id) }}" onsubmit="return confirm('Accept this transfer? Every item has been assigned a destination bin and will become Available immediately at {{ $transfer->to_location }}.')">
  @csrf
  <div class="stat-card overflow-hidden p-0">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Parts ({{ $items->count() }})</h2>
      @if($transfer->status === 'in_transit')
      <p class="text-xs text-gray-400 mt-0.5">Select a destination bin for every item before accepting — required to confirm receipt.</p>
      @endif
    </div>
    <table class="w-full text-sm font-body">
      <tbody>
        @foreach($items as $item)
        <tr class="border-b border-gray-50 last:border-0">
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $item->part_name }}</div>
            <div class="text-xs text-gray-400">{{ $item->part_code }} · {{ $item->brand }} {{ $item->model }}</div>
          </td>
          <td class="px-4 py-3 text-right">
            <span class="badge badge-gray">Grade {{ $item->condition_grade }}</span>
          </td>
          @if($transfer->status === 'in_transit')
          <td class="px-4 py-3">
            <select name="dest_bins[{{ $item->id }}]" required class="border-2 border-gold rounded-lg px-2 py-1.5 text-xs bg-white focus:outline-none w-full min-w-[200px]">
              <option value="">Select destination bin...</option>
              @foreach($toBins as $bin)
              <option value="{{ $bin->id }}">{{ $bin->room_name }} — {{ $bin->full_bin_code }}</option>
              @endforeach
            </select>
          </td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($transfer->status === 'in_transit')
  <div class="flex gap-3 mt-4">
    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-display font-700 text-sm py-3 rounded-xl tracking-wide transition-colors">
      ✓ Accept & Receive at {{ $transfer->to_location }}
    </button>
  </div>
  @endif
  </form>

  @if($transfer->status === 'in_transit')
  <form method="POST" action="{{ route('admin.transfers.cancel', $transfer->id) }}" onsubmit="return confirm('Cancel this transfer? Parts will be restored to Available at {{ $transfer->from_location }}.')" class="mt-3">
    @csrf
    <button type="submit" class="w-full border border-red-200 text-red-500 hover:bg-red-50 font-body font-500 text-sm px-6 py-3 rounded-xl transition-colors">
      Cancel Transfer
    </button>
  </form>
  @endif

</div>
@endsection
