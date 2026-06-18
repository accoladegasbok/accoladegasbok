{{-- FILE: resources/views/admin/orders/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Orders')
@section('page-title','Orders')
@section('page-sub','Manage customer orders, confirm payments, update status')

@section('header-actions')
<div class="text-right">
  <div class="text-xs text-gray-400 font-body">Today's confirmed revenue</div>
  <div class="font-display font-700 text-navy text-lg">₦{{ number_format($todayRevenue) }}</div>
</div>
@endsection

@section('content')

{{-- ── Status filter tabs ──────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2 mb-5">
  @foreach([
    ''                        => ['All Orders',          null],
    'pending'                 => ['Pending Payment',     'badge-amber'],
    'transfer_sent'           => ['Transfer Sent',       'badge-blue'],
    'confirmed'               => ['Confirmed',           'badge-green'],
    'payment_pending_confirmation' => ['Awaiting Confirm','badge-amber'],
  ] as $val => [$lbl, $badge])
  <a href="{{ request()->fullUrlWithQuery(['payment'=>$val, 'page'=>1]) }}"
     class="inline-flex items-center gap-1.5 text-xs font-body font-500 px-3 py-1.5 rounded-full border transition-colors
       {{ request('payment')===$val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">
    {{ $lbl }}
    @if(isset($counts[$val]) && $counts[$val] > 0)
      <span class="font-mono">{{ $counts[$val] }}</span>
    @endif
  </a>
  @endforeach
</div>

{{-- ── Search + filters ────────────────────────────────────────────────────── --}}
<form method="GET" class="flex flex-wrap gap-2 mb-5">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search order ref, name, phone..."
    class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">

  <select name="method" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
    <option value="">All methods</option>
    <option value="bank_transfer" {{ request('method')==='bank_transfer'?'selected':'' }}>Bank Transfer</option>
    <option value="pos_instore"   {{ request('method')==='pos_instore'?'selected':'' }}>POS In-Store</option>
  </select>

  <select name="status" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
    <option value="">All statuses</option>
    @foreach(['awaiting_payment','payment_pending_confirmation','confirmed','processing','ready_for_collection','shipped','completed','cancelled'] as $s)
      <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
    @endforeach
  </select>

  <button type="submit" class="bg-navy text-white font-display font-700 text-xs px-4 py-2.5 rounded-xl tracking-wide hover:bg-navy-light transition-colors">Search</button>
  @if(request()->hasAny(['q','method','status','payment']))
    <a href="{{ route('admin.orders.index') }}" class="border border-gray-200 text-gray-500 font-body text-xs px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">Clear</a>
  @endif
</form>

{{-- ── Orders table ─────────────────────────────────────────────────────────── --}}
<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100 bg-gray-50">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Order</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Customer</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Payment</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors" id="order-row-{{ $o->id }}">

          {{-- Order ref --}}
          <td class="px-4 py-3">
            <div class="font-mono text-xs text-navy font-700">{{ $o->order_ref }}</div>
            <div class="text-xs text-gray-400 mt-0.5">
              {{ $o->payment_method === 'bank_transfer' ? '🏦 Bank transfer' : '💳 POS in-store' }}
            </div>
          </td>

          {{-- Customer --}}
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $o->customer_name }}</div>
            <div class="text-xs text-gray-400">{{ $o->customer_phone }}</div>
            @if($o->customer_whatsapp && $o->customer_whatsapp !== $o->customer_phone)
              <div class="text-xs text-green-600">WA: {{ $o->customer_whatsapp }}</div>
            @endif
          </td>

          {{-- Payment status --}}
          <td class="px-4 py-3">
            <span id="pay-badge-{{ $o->id }}" class="badge
              @if($o->payment_status==='confirmed') badge-green
              @elseif(in_array($o->payment_status,['pending','awaiting_payment'])) badge-amber
              @elseif($o->payment_status==='transfer_sent') badge-blue
              @elseif($o->payment_status==='failed') badge-red
              @else badge-gray @endif">
              {{ str_replace('_',' ', $o->payment_status) }}
            </span>
            @if($o->transfer_reference)
              <div class="text-xs text-gray-400 mt-1 font-mono">Ref: {{ $o->transfer_reference }}</div>
            @endif
          </td>

          {{-- Amount --}}
          <td class="px-4 py-3">
            <div class="font-display font-700 text-navy">₦{{ number_format($o->total_amount_ngn) }}</div>
            <div class="text-xs text-gray-400">${{ number_format($o->total_amount_usd, 2) }}</div>
          </td>

          {{-- Order status --}}
          <td class="px-4 py-3">
            <select onchange="updateStatus({{ $o->id }}, this.value, this)"
              class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-body bg-white focus:outline-none focus:border-yellow-400 min-w-36">
              @foreach([
                'awaiting_payment'              => 'Awaiting Payment',
                'payment_pending_confirmation'  => 'Pending Confirm',
                'confirmed'                     => 'Confirmed',
                'processing'                    => 'Processing',
                'ready_for_collection'          => 'Ready for Collection',
                'shipped'                       => 'Shipped',
                'completed'                     => 'Completed',
                'cancelled'                     => 'Cancelled',
              ] as $val => $lbl)
                <option value="{{ $val }}" {{ $o->order_status===$val?'selected':'' }}>{{ $lbl }}</option>
              @endforeach
            </select>
          </td>

          {{-- Date --}}
          <td class="px-4 py-3">
            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }}</div>
            <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($o->created_at)->format('H:i') }}</div>
          </td>

          {{-- Actions --}}
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              {{-- Confirm payment button (only for pending/transfer_sent) --}}
              @if(in_array($o->payment_status, ['pending','transfer_sent','payment_pending_confirmation']))
                <button onclick="confirmPayment({{ $o->id }}, this)"
                  class="bg-green-500 hover:bg-green-600 text-white text-xs font-500 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                  Confirm ₦
                </button>
              @endif

              {{-- View detail --}}
              <a href="{{ route('admin.orders.show', $o->id) }}"
                class="border border-gray-200 text-gray-500 hover:border-navy hover:text-navy text-xs px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                View
              </a>

              {{-- WhatsApp customer --}}
              @php
                $waNum = str_contains($o->customer_country ?? '', 'Nigeria') || str_contains($o->customer_country ?? '', 'Ghana')
                    ? ltrim($o->customer_whatsapp ?? $o->customer_phone, '+0')
                    : ltrim($o->customer_phone, '+0');
                $waMsg = urlencode("Hi {$o->customer_name}, regarding your Auto Zenith order {$o->order_ref} (₦".number_format($o->total_amount_ngn).").");
              @endphp
              <a href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}" target="_blank"
                class="text-green-600 hover:text-green-800 transition-colors" title="WhatsApp customer">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
              </a>
            </div>
          </td>

        </tr>
        @empty
        <tr>
          <td colspan="7" class="px-4 py-12 text-center text-gray-400 font-body text-sm">
            No orders found matching your filters.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($orders->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">
    {{ $orders->links() }}
  </div>
  @endif
</div>

@endsection

@push('scripts')
<script>
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const staffName = '{{ session("staff_name") }}';

async function confirmPayment(orderId, btn) {
  if (!confirm('Confirm payment received for order ' + orderId + '?')) return;
  btn.disabled = true;
  btn.textContent = '...';

  const res  = await fetch(`/admin/orders/${orderId}/confirm-payment`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ confirmed_by: staffName }),
  });
  const data = await res.json();

  if (data.success) {
    btn.remove();
    const badge = document.getElementById('pay-badge-' + orderId);
    if (badge) {
      badge.textContent = 'confirmed';
      badge.className   = 'badge badge-green';
    }
  } else {
    btn.disabled = false;
    btn.textContent = 'Confirm ₦';
    alert('Error. Please try again.');
  }
}

async function updateStatus(orderId, status, sel) {
  const res  = await fetch(`/admin/orders/${orderId}/status`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ order_status: status }),
  });
  const data = await res.json();
  if (!data.success) {
    alert('Could not update status.');
    sel.value = sel.dataset.prev || '';
  } else {
    sel.dataset.prev = status;
    // Visual feedback
    const row = document.getElementById('order-row-' + orderId);
    if (row) {
      row.style.opacity = '.5';
      setTimeout(() => row.style.opacity = '1', 600);
    }
  }
}
</script>
@endpush
