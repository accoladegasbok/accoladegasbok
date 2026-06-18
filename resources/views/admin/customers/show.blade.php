{{-- FILE: resources/views/admin/customers/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Customer History')
@section('page-title', $name)
@section('page-sub', $phone)
@section('header-actions')
<a href="{{ route('admin.customers.index') }}"
   class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  ← Back to Customers
</a>
@endsection
@section('content')

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Spent</div>
    <div class="font-display font-700 text-navy text-2xl">${{ number_format($totalSpent, 2) }}</div>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Transactions</div>
    <div class="font-display font-700 text-navy text-2xl">{{ $totalCount }}</div>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Contact</div>
    <div class="font-500 text-navy text-sm">{{ $email ?: 'No email on file' }}</div>
    <a href="https://wa.me/{{ $phone }}" target="_blank" class="text-green-600 hover:text-green-700 text-xs font-500">
      Message on WhatsApp →
    </a>
  </div>
</div>

{{-- Most purchased items --}}
@if($topItems->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Most Purchased Items</h2>
  <div class="flex flex-wrap gap-2">
    @foreach($topItems as $item)
    <span class="bg-gray-50 border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">
      {{ $item->part_name }} <span class="text-gray-400">×{{ $item->count }}</span>
    </span>
    @endforeach
  </div>
</div>
@endif

{{-- Online orders --}}
@if($orders->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Online Orders ({{ $orders->count() }})</h2>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-gray-100">
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Order Ref</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
        <th class="px-5 py-2.5"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $o)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-5 py-3 font-mono text-navy text-xs">{{ $o->order_ref }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($o->created_at)->format('M j, Y') }}</td>
        <td class="px-5 py-3">
          <span class="badge badge-blue">{{ str_replace('_', ' ', $o->order_status) }}</span>
        </td>
        <td class="px-5 py-3 font-display font-700 text-navy text-xs">${{ number_format($o->total_amount_usd, 2) }}</td>
        <td class="px-5 py-3 text-right">
          <a href="{{ route('admin.orders.show', $o->id) }}" class="text-gold hover:text-yellow-600 text-xs font-500">View →</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- Manual invoices --}}
@if($invoices->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">In-Store / Phone Invoices ({{ $invoices->count() }})</h2>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-gray-100">
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Invoice No</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
        <th class="px-5 py-2.5"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoices as $inv)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-5 py-3 font-mono text-navy text-xs">{{ $inv->invoice_no }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($inv->created_at)->format('M j, Y') }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ $inv->location }}</td>
        <td class="px-5 py-3 font-display font-700 text-navy text-xs">${{ number_format($inv->subtotal_usd, 2) }}</td>
        <td class="px-5 py-3 text-right">
          <a href="{{ route('admin.invoices.show.manual', $inv->id) }}" class="text-gold hover:text-yellow-600 text-xs font-500">View →</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

@endsection
