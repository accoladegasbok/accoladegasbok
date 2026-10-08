{{-- FILE: resources/views/admin/customer-credit/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Customer Credit')
@section('page-title', 'Customer Credit')
@section('page-sub', 'Overpayments and return credit, per customer and per currency — never mixed')

@php $sym = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$']; @endphp

@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<div class="flex flex-wrap items-center gap-3 mb-4">
  @forelse($totals as $code => $bal)
    <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-body">Owed to customers ({{ $code }}): <span class="font-display font-700 text-navy text-lg">{{ $sym[$code] ?? '' }}{{ $code === 'NGN' ? number_format($bal) : number_format($bal, 2) }}</span></div>
  @empty
    <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-body text-gray-500">No credit outstanding.</div>
  @endforelse
  <form method="GET" class="ml-auto flex gap-2 items-center">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search name or phone..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-60 focus:outline-none focus:border-gold">
    <label class="text-xs text-gray-500 flex items-center gap-1"><input type="checkbox" name="all" value="1" {{ request('all') ? 'checked' : '' }} onchange="this.form.submit()"> include zero</label>
  </form>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
      <tr><th class="text-left px-4 py-3">Customer</th><th class="text-left px-4 py-3">Phone ends</th><th class="text-left px-4 py-3">Balance</th><th class="text-left px-4 py-3">Last activity</th><th></th></tr>
    </thead>
    <tbody>
      @forelse($customers as $c)
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2.5 font-500 text-navy">{{ $c->customer_name ?: '—' }}</td>
        <td class="px-4 py-2.5 text-gray-500 font-mono">…{{ substr($c->phone_key, -6) }}</td>
        <td class="px-4 py-2.5 font-display font-700 text-navy">{{ $sym[$c->currency_code] ?? '' }}{{ $c->currency_code === 'NGN' ? number_format($c->balance) : number_format($c->balance, 2) }} <span class="text-xs text-gray-400">{{ $c->currency_code }}</span></td>
        <td class="px-4 py-2.5 text-gray-500">{{ \Carbon\Carbon::parse($c->last_activity)->format('d M Y') }}</td>
        <td class="px-4 py-2.5 text-right"><a href="{{ route('admin.customer-credit.show', $c->phone_key) }}" class="text-xs text-navy hover:text-gold">Open →</a></td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Nobody has credit right now.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mb-8">{{ $customers->links() }}</div>

@if($canManage)
<details class="bg-white rounded-2xl border border-gray-200 shadow-sm">
  <summary class="cursor-pointer px-5 py-4 font-display font-700 text-navy text-sm uppercase tracking-wide">Add credit by hand (supervisor and above)</summary>
  <form method="POST" action="{{ route('admin.customer-credit.adjust') }}" class="px-5 pb-5 grid grid-cols-1 sm:grid-cols-5 gap-3">
    @csrf
    <input type="text" name="phone" required placeholder="Customer phone *" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <input type="text" name="name" placeholder="Customer name" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <select name="currency" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white"><option value="NGN">₦ NGN</option><option value="GHS">GH₵ GHS</option><option value="USD">$ USD</option></select>
    <input type="number" step="0.01" name="amount" required placeholder="Amount (negative removes)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <input type="text" name="notes" required placeholder="Reason (required)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <button class="sm:col-span-5 sm:w-auto justify-self-start bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-5 py-2 rounded-lg">Save adjustment</button>
  </form>
</details>
@endif
@endsection
