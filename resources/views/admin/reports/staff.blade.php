{{-- FILE: resources/views/admin/reports/staff.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Staff Report')
@section('page-title', 'Staff Performance Report')
@section('page-sub', 'Sales by staff, commissions, and override activity')

@section('content')

<form method="GET" action="{{ route('admin.reports.staff') }}"
      class="bg-white border border-gray-200 rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-end shadow-sm">
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">From</label>
        <input type="date" name="from" value="{{ $from }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-yellow-400">
    </div>
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">To</label>
        <input type="date" name="to" value="{{ $to }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-yellow-400">
    </div>
    <button type="submit" class="bg-navy text-white font-700 text-sm px-5 py-2 rounded-xl">Apply</button>
</form>

{{-- Sales by Staff --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Sales by Staff Member</h3>
    @if($salesByStaff->isEmpty())
        <p class="text-xs text-gray-400 text-center py-6">No sales data for this period.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-navy text-white">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Staff</th>
                    <th class="px-4 py-2.5 text-center text-xs uppercase tracking-wide">Invoices</th>
                    <th class="px-4 py-2.5 text-right text-xs uppercase tracking-wide">Revenue</th>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Currency</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($salesByStaff as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-600 text-navy text-xs">{{ $s->staff_name ?: 'Unknown' }}</td>
                    <td class="px-4 py-2.5 text-center text-xs">{{ $s->invoice_count }}</td>
                    <td class="px-4 py-2.5 text-right font-700 text-gold text-sm">{{ number_format($s->total_revenue) }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500">{{ $s->currency_code }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Commissions --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Commission Summary</h3>
    @if($commissions->isEmpty())
        <p class="text-xs text-gray-400 text-center py-6">No commission data for this period.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-navy text-white">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Staff</th>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Role</th>
                    <th class="px-4 py-2.5 text-center text-xs uppercase tracking-wide">Sales</th>
                    <th class="px-4 py-2.5 text-right text-xs uppercase tracking-wide">Total Sales</th>
                    <th class="px-4 py-2.5 text-right text-xs uppercase tracking-wide">Commission</th>
                    <th class="px-4 py-2.5 text-right text-xs uppercase tracking-wide">Avg Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($commissions as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-600 text-navy text-xs">{{ $c->name }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500">{{ $c->role }}</td>
                    <td class="px-4 py-2.5 text-center text-xs">{{ $c->sale_count }}</td>
                    <td class="px-4 py-2.5 text-right text-xs">{{ number_format($c->total_sales) }} {{ $c->currency_code }}</td>
                    <td class="px-4 py-2.5 text-right font-700 text-gold text-xs">{{ number_format($c->total_commission) }}</td>
                    <td class="px-4 py-2.5 text-right text-xs text-gray-500">{{ round($c->avg_rate, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Override Activity --}}
<div class="stat-card">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Override / PIN Activity</h3>
    @if($overrides->isEmpty())
        <p class="text-xs text-gray-400 text-center py-6">No override activity for this period.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-navy text-white">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Staff</th>
                    <th class="px-4 py-2.5 text-left text-xs uppercase tracking-wide">Action</th>
                    <th class="px-4 py-2.5 text-center text-xs uppercase tracking-wide">Count</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($overrides as $o)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-600 text-navy text-xs">{{ $o->staff_name }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">{{ $o->action }}</td>
                    <td class="px-4 py-2.5 text-center font-700 text-xs">{{ $o->count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
