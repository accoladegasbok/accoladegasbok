{{-- FILE: resources/views/admin/ai-fitment/report.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'AI Fitment Report')
@section('page-title', 'AI Fitment — 30-day report')
@section('page-sub', 'How often AI suggestions are approved, and what happened to the parts sold after approval')

@section('header-actions')
<a href="{{ route('admin.ai-fitment.index') }}" class="text-xs font-body text-navy border border-gray-200 bg-white rounded-xl px-3 py-2 hover:border-navy whitespace-nowrap">← Review queue</a>
@endsection

@php
  $pct = fn ($a, $b) => $b > 0 ? number_format($a / $b * 100, 1) . '%' : '—';
@endphp

@section('content')
<div class="flex flex-wrap gap-2 mb-5">
  @foreach([30 => 'Last 30 days', 90 => 'Last 90 days', 180 => 'Last 6 months', 365 => 'Last year'] as $d => $label)
  <a href="{{ route('admin.ai-fitment.report', ['days' => $d]) }}" class="px-3.5 py-1.5 rounded-full text-xs font-display font-700 border {{ $days === $d ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy' }}">{{ $label }}</a>
  @endforeach
</div>

{{-- 1. Acceptance --}}
<h2 class="font-display font-700 text-navy text-lg uppercase tracking-wide mb-2">1. Are staff approving what the AI suggests?</h2>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr>
      <th class="text-left px-4 py-3">Confidence</th><th class="text-right px-4 py-3">Suggested</th><th class="text-right px-4 py-3">Approved</th><th class="text-right px-4 py-3">Rejected</th><th class="text-right px-4 py-3">Still waiting</th><th class="text-right px-4 py-3">Approval rate</th>
    </tr></thead>
    <tbody>
      @php $T = ['confirmed' => 0, 'rejected' => 0, 'pending' => 0]; @endphp
      @foreach(['high', 'medium', 'low'] as $c)
      @php $g = $byConf[$c] ?? collect(); $a = (int) ($g['confirmed'] ?? 0); $r = (int) ($g['rejected'] ?? 0); $p = (int) ($g['pending'] ?? 0); $T['confirmed'] += $a; $T['rejected'] += $r; $T['pending'] += $p; @endphp
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2.5 font-500">{{ ucfirst($c) }}</td><td class="px-4 py-2.5 text-right">{{ $a + $r + $p }}</td><td class="px-4 py-2.5 text-right text-green-700">{{ $a }}</td>
        <td class="px-4 py-2.5 text-right text-red-600">{{ $r }}</td><td class="px-4 py-2.5 text-right text-gray-500">{{ $p }}</td><td class="px-4 py-2.5 text-right font-700">{{ $pct($a, $a + $r) }}</td>
      </tr>
      @endforeach
      <tr class="border-t-2 border-gray-200 bg-gray-50 font-700"><td class="px-4 py-2.5">All</td><td class="px-4 py-2.5 text-right">{{ array_sum($T) }}</td><td class="px-4 py-2.5 text-right">{{ $T['confirmed'] }}</td><td class="px-4 py-2.5 text-right">{{ $T['rejected'] }}</td><td class="px-4 py-2.5 text-right">{{ $T['pending'] }}</td><td class="px-4 py-2.5 text-right">{{ $pct($T['confirmed'], $T['confirmed'] + $T['rejected']) }}</td></tr>
    </tbody>
  </table>
</div>

{{-- 2. Outcome --}}
<h2 class="font-display font-700 text-navy text-lg uppercase tracking-wide mb-2">2. What happened after approval (first 30 days)</h2>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
  <div class="stat-card"><div class="text-xs text-gray-400 uppercase tracking-wider">Parts sold in approved groups</div><div class="font-display font-800 text-navy text-2xl">{{ number_format($totals['sold']) }}</div></div>
  <div class="stat-card"><div class="text-xs text-gray-400 uppercase tracking-wider">Returned within 30 days</div><div class="font-display font-800 text-navy text-2xl">{{ number_format($totals['returned']) }} <span class="text-sm text-gray-400">{{ $pct($totals['returned'], $totals['sold']) }}</span></div></div>
  <div class="stat-card"><div class="text-xs text-gray-400 uppercase tracking-wider">…because it did not fit</div><div class="font-display font-800 text-navy text-2xl">{{ number_format($totals['fit']) }}</div></div>
  <div class="stat-card"><div class="text-xs text-gray-400 uppercase tracking-wider">All sales, for comparison</div><div class="font-display font-800 text-navy text-2xl">{{ $pct($baseline['returned'], $baseline['sold']) }} <span class="text-sm text-gray-400">returned</span></div></div>
</div>
<p class="text-xs text-gray-400 font-body mb-3">"Because it did not fit" counts returns whose reason mentions the word "fit". A group's sales are counted for the 30 days after its first approval; they include sales of parts that were already in the group, so treat the return rate as a signal to investigate, not proof.</p>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr>
      <th class="text-left px-4 py-3">Group</th><th class="text-left px-4 py-3">Approved</th><th class="text-right px-4 py-3">Sold (30 d)</th><th class="text-right px-4 py-3">Returned</th><th class="text-right px-4 py-3">Did not fit</th>
    </tr></thead>
    <tbody>
      @forelse($perGroup as $g)
      <tr class="border-t border-gray-100 {{ $g->fit_returned > 0 ? 'bg-red-50' : '' }}">
        <td class="px-4 py-2.5"><span class="font-500 text-navy">{{ $g->group->part_name ?? '—' }}</span> <span class="font-mono text-xs text-gray-400">{{ $g->group->group_code ?? '' }}</span></td>
        <td class="px-4 py-2.5 text-gray-500">{{ \Carbon\Carbon::parse($g->approved_at)->format('d M Y') }}</td>
        <td class="px-4 py-2.5 text-right">{{ $g->sold }}</td><td class="px-4 py-2.5 text-right">{{ $g->returned }}</td>
        <td class="px-4 py-2.5 text-right {{ $g->fit_returned ? 'text-red-600 font-700' : '' }}">{{ $g->fit_returned }}</td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No approved fitments in this period yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- 3. Audit --}}
<h2 class="font-display font-700 text-navy text-lg uppercase tracking-wide mb-2">3. Recent review batches (audit log)</h2>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr><th class="text-left px-4 py-3">When</th><th class="text-left px-4 py-3">Who</th><th class="text-left px-4 py-3">Action</th><th class="text-right px-4 py-3">Items</th><th class="text-left px-4 py-3">Note</th></tr></thead>
    <tbody>
      @forelse($batches as $b)
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2.5 text-gray-500">{{ \Carbon\Carbon::parse($b->created_at)->format('d M Y, H:i') }}</td><td class="px-4 py-2.5">{{ $b->staff_name }}</td>
        <td class="px-4 py-2.5"><span class="badge {{ $b->action === 'confirm' ? 'badge-green' : 'badge-red' }}">{{ $b->action === 'confirm' ? 'Approved' : 'Rejected' }}</span></td>
        <td class="px-4 py-2.5 text-right">{{ $b->item_count }}</td><td class="px-4 py-2.5 text-gray-500 text-xs">{{ $b->note }}</td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No reviews yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
