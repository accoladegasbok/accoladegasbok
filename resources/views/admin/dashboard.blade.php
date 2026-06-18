{{-- FILE: resources/views/admin/dashboard.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('page-sub','Auto Zenith Parts — inventory & operations overview')

@section('content')

{{-- ── KPI Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
  @foreach([
    ['label'=>'Available',       'value'=>number_format($available),      'color'=>'text-green-700',  'bg'=>'bg-green-50'],
    ['label'=>'Reserved',        'value'=>number_format($reserved),       'color'=>'text-blue-700',   'bg'=>'bg-blue-50'],
    ['label'=>'Sold',            'value'=>number_format($sold),           'color'=>'text-gray-600',   'bg'=>'bg-gray-50'],
    ['label'=>'Donor Vehicles',  'value'=>number_format($donorCount),     'color'=>'text-purple-700', 'bg'=>'bg-purple-50'],
    ['label'=>'Orders Today',    'value'=>number_format($ordersToday),    'color'=>'text-navy',       'bg'=>'bg-amber-50'],
    ['label'=>'Awaiting Payment','value'=>number_format($pendingPayments),'color'=>'text-red-700',    'bg'=>'bg-red-50'],
  ] as $k)
  <div class="stat-card {{ $k['bg'] }} border-0">
    <div class="text-xs font-body text-gray-500 mb-1">{{ $k['label'] }}</div>
    <div class="font-display font-700 text-2xl {{ $k['color'] }}">{{ $k['value'] }}</div>
  </div>
  @endforeach
</div>

{{-- ── Revenue + Inventory value ────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
  <div class="stat-card">
    <div class="text-xs font-body text-gray-400 mb-1">Today's confirmed revenue</div>
    <div class="font-display font-700 text-2xl text-navy">₦{{ number_format($revenueToday) }}</div>
  </div>
  <div class="stat-card">
    <div class="text-xs font-body text-gray-400 mb-1">Available inventory value</div>
    <div class="font-display font-700 text-2xl text-navy">${{ number_format($totalValueUsd, 0) }}</div>
    <div class="text-xs text-gray-400 font-body">≈ ₦{{ number_format($totalValueUsd * 1600, 0) }}</div>
  </div>
  <div class="stat-card {{ $lowStock > 0 ? 'border-amber-300 bg-amber-50' : '' }}">
    <div class="text-xs font-body text-gray-400 mb-1">Low / single-stock parts</div>
    <div class="font-display font-700 text-2xl {{ $lowStock > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ $lowStock }}</div>
    @if($lowStock > 0)<div class="text-xs text-amber-600 font-body">Review pricing or source more</div>@endif
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- ── Recent Orders ───────────────────────────────────────────────── --}}
  <div class="lg:col-span-2 stat-card">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase">Recent Orders</h2>
      <a href="{{ route('admin.orders.index') }}" class="text-xs font-body text-blue-600 hover:underline">View all</a>
    </div>

    @if($recentOrders->isEmpty())
      <p class="text-gray-400 text-sm font-body text-center py-6">No orders yet.</p>
    @else
    <div class="space-y-2">
      @foreach($recentOrders as $o)
      <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-mono text-xs text-gray-400">{{ $o->order_ref }}</span>
            <span class="badge
              @if($o->payment_status==='confirmed') badge-green
              @elseif($o->payment_status==='transfer_sent') badge-blue
              @elseif($o->payment_status==='pending') badge-amber
              @else badge-gray @endif">
              {{ str_replace('_',' ', $o->payment_status) }}
            </span>
          </div>
          <div class="text-sm font-body font-500 text-navy truncate">{{ $o->customer_name }}</div>
          <div class="text-xs text-gray-400 font-body">{{ $o->payment_method === 'bank_transfer' ? 'Bank transfer' : 'POS in-store' }}</div>
        </div>
        <div class="text-right flex-shrink-0">
          <div class="font-display font-700 text-navy text-sm">₦{{ number_format($o->total_amount_ngn) }}</div>
          <div class="text-xs text-gray-400 font-body">{{ \Carbon\Carbon::parse($o->created_at)->diffForHumans() }}</div>
        </div>
        @if(in_array($o->payment_status, ['pending','transfer_sent']))
        <button onclick="confirmPayment({{ $o->id }}, this)"
          class="text-xs font-body font-500 bg-green-500 text-white px-3 py-1.5 rounded-lg hover:bg-green-600 transition-colors whitespace-nowrap">
          Confirm
        </button>
        @endif
      </div>
      @endforeach
    </div>
    @endif
  </div>

  {{-- ── Right column ─────────────────────────────────────────────── --}}
  <div class="space-y-5">

    {{-- Inventory by brand --}}
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Stock by Brand</h2>
      <div class="space-y-2">
        @foreach($byBrand->take(8) as $b)
        <div class="flex items-center gap-2">
          <span class="text-xs font-body text-gray-600 w-24 truncate">{{ $b->brand }}</span>
          <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="bg-navy h-2 rounded-full" style="width:{{ min(100, $b->total / max($byBrand->max('total'),1) * 100) }}%"></div>
          </div>
          <span class="text-xs font-mono text-gray-400 w-6 text-right">{{ $b->total }}</span>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Recent harvests --}}
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase">Recent Harvests</h2>
        <a href="{{ route('admin.harvest.create') }}" class="text-xs font-body text-blue-600 hover:underline">+ New</a>
      </div>
      @if($recentHarvests->isEmpty())
        <p class="text-gray-400 text-xs font-body text-center py-4">No harvests yet.</p>
      @else
      <div class="space-y-2.5">
        @foreach($recentHarvests as $h)
        <div class="flex items-start gap-2.5 border-b border-gray-50 pb-2.5 last:border-0 last:pb-0">
          <div class="flex-1 min-w-0">
            <div class="text-sm font-body font-500 text-navy truncate">{{ $h->year }} {{ $h->make }} {{ $h->model }}</div>
            <div class="text-xs text-gray-400 font-mono">{{ $h->vin }}</div>
            <div class="text-xs text-gray-400 font-body">{{ $h->staff_name }} · {{ $h->parts_listed }} parts</div>
          </div>
          <span class="badge {{ $h->status==='completed' ? 'badge-green' : 'badge-amber' }} flex-shrink-0">
            {{ $h->status }}
          </span>
        </div>
        @endforeach
      </div>
      @endif
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function confirmPayment(orderId, btn) {
  if (!confirm('Confirm payment received for this order?')) return;
  btn.disabled = true;
  btn.textContent = '...';

  const res  = await fetch(`/admin/orders/${orderId}/confirm-payment`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ confirmed_by: '{{ session("staff_name") }}' }),
  });
  const data = await res.json();

  if (data.success) {
    btn.textContent = 'Confirmed!';
    btn.classList.remove('bg-green-500','hover:bg-green-600');
    btn.classList.add('bg-gray-200','text-gray-500');
    btn.closest('.flex').querySelector('.badge').textContent = 'confirmed';
    btn.closest('.flex').querySelector('.badge').className = 'badge badge-green';
  } else {
    btn.disabled = false;
    btn.textContent = 'Confirm';
    alert('Error confirming payment.');
  }
}
</script>
@endpush
