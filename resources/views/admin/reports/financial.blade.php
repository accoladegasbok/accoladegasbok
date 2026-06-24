{{-- FILE: resources/views/admin/reports/financial.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Financial Reports')
@section('page-title','Financial Reports')
@section('page-sub','Revenue by location and staff — ' . \Carbon\Carbon::parse($from)->format('M j, Y') . ' to ' . \Carbon\Carbon::parse($to)->format('M j, Y'))
@section('content')

{{-- Period toggle --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-5 flex items-center justify-between flex-wrap gap-3">
  <div class="flex gap-2">
    @foreach(['daily' => 'Today', 'weekly' => 'This Week', 'monthly' => 'This Month'] as $key => $label)
    <a href="{{ route('admin.reports.financial', ['period' => $key]) }}"
      class="px-4 py-2 rounded-lg text-sm font-display font-700 transition-colors {{ $period === $key && !request('from') ? 'bg-navy text-white' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
      {{ $label }}
    </a>
    @endforeach
  </div>

  <form method="GET" action="{{ route('admin.reports.financial') }}" class="flex items-center gap-2">
    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    <span class="text-gray-400 text-sm">to</span>
    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-4 py-2 rounded-lg transition-colors">
      Custom Range
    </button>
  </form>
</div>

{{-- ── Revenue PER CURRENCY — never blended into one total ──────────── --}}
<p class="text-xs text-gray-400 font-body mb-3 uppercase tracking-wider">Revenue by Currency (no cross-currency conversion)</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @forelse($revenueByCurrency as $rc)
  <div class="bg-navy rounded-2xl p-6">
    <div class="text-gray-300 text-xs uppercase tracking-wider mb-1">{{ $rc->currency_code }} Revenue</div>
    <div class="font-display font-800 text-white text-2xl">{{ $rc->symbol }}{{ number_format($rc->total, $rc->currency_code === 'NGN' ? 0 : 2) }}</div>
    <div class="text-gray-400 text-xs mt-1">{{ $rc->count }} transaction{{ $rc->count !== 1 ? 's' : '' }}</div>
  </div>
  @empty
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 col-span-full text-center text-gray-400 text-sm">
    No revenue in this period.
  </div>
  @endforelse
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col justify-center">
    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Total Transactions</div>
    <div class="font-display font-800 text-navy text-2xl">{{ $totalTransactions }}</div>
    <div class="text-gray-400 text-xs mt-1">across all currencies</div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  {{-- Revenue by location — each row keeps its own currency, no summing across currencies --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Revenue by Location</h2>
    </div>
    <table class="w-full text-sm">
      <tbody>
        @forelse($byLocation as $loc)
        <tr class="border-b border-gray-50">
          <td class="px-5 py-3 font-500 text-navy text-xs">
            {{ $loc->location }}
            <span class="text-gray-400 font-400">({{ $loc->currency_code }})</span>
          </td>
          <td class="px-5 py-3 text-gray-500 text-xs text-right">{{ $loc->count }} txn</td>
          <td class="px-5 py-3 font-display font-700 text-navy text-sm text-right">
            {{ $loc->symbol }}{{ number_format($loc->revenue, $loc->currency_code === 'NGN' ? 0 : 2) }}
          </td>
        </tr>
        @empty
        <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400 text-sm">No revenue in this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Revenue by staff — each row keeps its own currency --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Revenue by Staff</h2>
    </div>
    <table class="w-full text-sm">
      <tbody>
        @forelse($byStaff as $s)
        <tr class="border-b border-gray-50">
          <td class="px-5 py-3">
            <div class="font-500 text-navy text-xs">{{ $s->staff }}</div>
            <div class="text-xs text-gray-400">{{ $s->currency_code }}</div>
          </td>
          <td class="px-5 py-3 text-gray-500 text-xs text-right">{{ $s->count }} txn</td>
          <td class="px-5 py-3 font-display font-700 text-navy text-sm text-right">
            {{ $s->symbol }}{{ number_format($s->revenue, $s->currency_code === 'NGN' ? 0 : 2) }}
          </td>
        </tr>
        @empty
        <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400 text-sm">No revenue in this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>

{{-- Daily trend — one chart PER CURRENCY, never combined --}}
@php $trendByCurrency = $trend->groupBy('currency_code'); @endphp
@foreach($trendByCurrency as $code => $days)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mt-5">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Daily Trend — {{ $code }}</h2>
  <div class="flex items-end gap-1.5 h-32">
    @php $maxRevenue = $days->max('revenue') ?: 1; $symbol = \App\Http\Controllers\Admin\InvoiceController::currencyMeta($code)['symbol']; @endphp
    @foreach($days as $day)
    <div class="flex-1 flex flex-col items-center gap-1 group relative">
      <div class="w-full bg-gold rounded-t hover:bg-yellow-500 transition-colors" style="height: {{ max(4, ($day->revenue / $maxRevenue) * 100) }}px;"></div>
      <div class="text-xs text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse($day->date)->format('M j') }}</div>
      <div class="absolute -top-7 bg-navy text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
        {{ $symbol }}{{ number_format($day->revenue, $code === 'NGN' ? 0 : 2) }}
      </div>
    </div>
    @endforeach
  </div>
</div>
@endforeach

@endsection
