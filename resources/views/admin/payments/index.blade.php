{{-- FILE: resources/views/admin/payments/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Payments')
@section('page-title','Payments Ledger')
@section('page-sub','Every payment recorded across Orders and Invoices — proofs, confirmations, and status, all in one place')

@section('content')

{{-- ── Currency tabs — genuinely separate views, never blended ──── --}}
<div class="flex gap-2 mb-4">
  <a href="{{ request()->fullUrlWithQuery(['currency' => '']) }}"
     class="text-sm font-body font-500 px-4 py-2 rounded-full border {{ $currency==='' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">
    All Currencies
  </a>
  @foreach(['NGN' => '₦ Naira', 'USD' => '$ Dollar', 'GHS' => 'GH₵ Cedi'] as $code => $label)
  <a href="{{ request()->fullUrlWithQuery(['currency' => $code]) }}"
     class="text-sm font-body font-500 px-4 py-2 rounded-full border {{ $currency===$code ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">
    {{ $label }} @if(($currencyCounts[$code] ?? 0) > 0)<span class="font-mono text-xs">({{ $currencyCounts[$code] }})</span>@endif
  </a>
  @endforeach
</div>

@if($currency && $confirmedTotal !== null)
@php $tabSym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$currency] ?? '$'; @endphp
<div class="bg-green-50 border border-green-200 rounded-xl px-5 py-3 mb-4 text-sm font-body">
  <strong>Total confirmed in {{ $currency }}:</strong>
  {{ $tabSym }}{{ $currency === 'NGN' ? number_format($confirmedTotal) : number_format($confirmedTotal, 2) }}
</div>
@endif

<div class="grid grid-cols-3 gap-3 mb-5">
  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
    <div class="text-xs text-amber-600 uppercase tracking-wider">Pending Confirmation</div>
    <div class="font-display font-800 text-amber-700 text-2xl">{{ $summary['pending'] ?? 0 }}</div>
  </div>
  <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
    <div class="text-xs text-green-600 uppercase tracking-wider">Confirmed</div>
    <div class="font-display font-800 text-green-700 text-2xl">{{ $summary['confirmed'] ?? 0 }}</div>
  </div>
  <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
    <div class="text-xs text-red-600 uppercase tracking-wider">Rejected</div>
    <div class="font-display font-800 text-red-700 text-2xl">{{ $summary['rejected'] ?? 0 }}</div>
  </div>
</div>

<form method="GET" class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-4">
  <input type="hidden" name="currency" value="{{ $currency }}">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, ref#..."
    class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
  <input type="date" name="date_from" value="{{ request('date_from') }}" title="From date"
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
  <input type="date" name="date_to" value="{{ request('date_to') }}" title="To date"
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
  <select name="status" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Statuses</option>
    <option value="pending" {{ request('status')==='pending'?'selected':'' }}>Pending</option>
    <option value="confirmed" {{ request('status')==='confirmed'?'selected':'' }}>Confirmed</option>
    <option value="rejected" {{ request('status')==='rejected'?'selected':'' }}>Rejected</option>
  </select>
  <button type="submit" class="sm:col-span-5 sm:w-auto justify-self-start bg-navy text-white font-display font-700 text-sm rounded-lg px-5 py-2 hover:bg-navy-light transition-colors">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Source</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Customer</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Method</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Proof</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Confirmed By</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($payments as $p)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-4 py-3 text-xs text-gray-400">{{ \Carbon\Carbon::parse($p->created_at)->format('d M Y, H:i') }}</td>
        <td class="px-4 py-3">
          <span class="badge {{ $p->source_type === 'order' ? 'badge-blue' : 'badge-green' }}">{{ ucfirst($p->source_type) }}</span>
          <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $p->ref_no }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="font-500 text-navy">{{ $p->customer_name }}</div>
          <div class="text-xs text-gray-400">{{ $p->customer_phone }}</div>
        </td>
        <td class="px-4 py-3 font-display font-700 text-navy">{{ ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$p->currency_code ?? 'NGN'] ?? '₦' }}{{ number_format($p->amount) }}</td>
        <td class="px-4 py-3 text-gray-600">{{ $p->payment_method }}</td>
        <td class="px-4 py-3">
          @if($p->proof_path)
            <a href="{{ asset(config('media.prefix') . '/' . $p->proof_path) }}" target="_blank" class="text-gold hover:text-yellow-600 text-xs">View →</a>
          @else
            <span class="text-gray-300 text-xs">No proof</span>
          @endif
        </td>
        <td class="px-4 py-3">
          <span class="badge {{ $p->status==='confirmed' ? 'badge-green' : ($p->status==='rejected' ? 'badge-red' : 'badge-amber') }}">{{ ucfirst($p->status) }}</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500">
          {{ $p->confirmed_by_name ?? '—' }}
          @if($p->confirmed_at)<div class="text-gray-400">{{ \Carbon\Carbon::parse($p->confirmed_at)->format('d M, H:i') }}</div>@endif
        </td>
        <td class="px-4 py-3 text-right">
          @if($p->source_type === 'order')
            <a href="{{ route('admin.orders.show', $p->ref_id) }}" class="text-xs font-body text-navy hover:text-gold">View Order →</a>
          @else
            <a href="{{ route('admin.invoices.show.manual', $p->ref_id) }}" target="_blank" class="text-xs font-body text-navy hover:text-gold">View Invoice →</a>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">No payments found.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($payments->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $payments->links() }}</div>
  @endif
</div>

@endsection
