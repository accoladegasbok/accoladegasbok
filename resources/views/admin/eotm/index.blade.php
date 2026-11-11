{{-- FILE: resources/views/admin/eotm/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Employee of the Month')
@section('page-title', 'Employee of the Month')
@section('page-sub', 'Top 3 each month by score, then one vote per staff member picks the winner')

@section('header-actions')
  @if($isSup)<a href="{{ route('admin.eotm.rate', ['ym' => $ym]) }}" class="bg-gold text-navy font-display font-700 text-xs px-3 py-2 rounded-xl">Rate staff</a>@endif
@endsection

@section('content')
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
@php
  $monthLabel = \Carbon\Carbon::parse($ym.'-01')->format('F Y');
  $prev = \Carbon\Carbon::parse($ym.'-01')->subMonth()->format('Y-m'); $next = \Carbon\Carbon::parse($ym.'-01')->addMonth()->format('Y-m');
  $rewardLabels = ['cash' => 'Cash bonus', 'voucher' => 'Shopping voucher', 'salary' => 'Salary incentive', 'certificate' => 'Certificate of recognition', 'promotion' => 'Promotion opportunity'];
  $stateLabel = ['rating' => 'Scoring', 'voting' => 'Voting open', 'closed' => 'Closed'][$cycle->status];
@endphp

<div class="flex items-center gap-3 mb-5 font-body">
  <a href="{{ route('admin.eotm.index', ['ym' => $prev]) }}" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm">‹</a>
  <div class="font-display font-700 text-navy text-xl">{{ $monthLabel }}</div>
  <a href="{{ route('admin.eotm.index', ['ym' => $next]) }}" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm">›</a>
  <span class="text-xs px-2.5 py-1 rounded-full font-700 {{ $cycle->status==='voting' ? 'bg-amber-100 text-amber-800' : ($cycle->status==='closed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600') }}">{{ $stateLabel }}</span>
</div>

{{-- CLOSED: winner --}}
@if($cycle->status === 'closed' && $winner)
<div class="rounded-2xl bg-navy text-white p-6 mb-6 text-center">
  <div class="text-gold text-xs tracking-widest uppercase font-700">Employee of the Month · {{ $monthLabel }}</div>
  <div class="font-display font-700 text-3xl mt-2">{{ $winner->name }}</div>
  <div class="text-sm text-gray-300 font-body">{{ $winner->location }}</div>
  @if($rewards || $cycle->prize_amount)
    <div class="mt-3 text-sm font-body">
      @foreach($rewards as $k)<span class="inline-block bg-white/10 rounded-full px-3 py-1 mx-1 my-0.5">{{ $rewardLabels[$k] ?? $k }}</span>@endforeach
      @if($cycle->prize_amount)<div class="mt-2 text-gold font-700">{{ $cycle->prize_currency }} {{ number_format($cycle->prize_amount, 2) }}{{ $cycle->paid_at ? ' · paid' : ' · to be paid' }}</div>@endif
      @if($cycle->prize_note)<div class="text-gray-300 text-xs mt-1">{{ $cycle->prize_note }}</div>@endif
    </div>
  @endif
  <a href="{{ route('admin.eotm.certificate', $cycle->id) }}" class="inline-block mt-4 bg-gold text-navy font-display font-700 text-sm px-5 py-2 rounded-xl">View certificate</a>
</div>
@endif

{{-- NOMINEES (voting or closed) --}}
@if($nominees->isNotEmpty())
<div class="grid md:grid-cols-3 gap-4 mb-6">
  @foreach($nominees as $n)
  <div class="bg-white rounded-2xl border-2 {{ $cycle->winner_staff_id == $n->staff_id ? 'border-gold' : 'border-gray-200' }} p-5 {{ $n->disqualified ? 'opacity-50' : '' }}">
    <div class="text-[11px] text-gray-400 uppercase tracking-wider font-700">Nominee {{ $n->rank }}</div>
    <div class="font-display font-700 text-navy text-lg">{{ $n->name }}</div>
    <div class="text-xs text-gray-500 font-body">{{ $n->location }} · Staff ID {{ $n->staff_id }}</div>
    @if($cycle->status === 'closed' || $isMgr)
      <div class="mt-3 text-xs font-body space-y-0.5">
        <div>Performance score: <b>{{ $n->performance_score }}</b></div>
        @if($cycle->status === 'closed')
          <div>Votes: <b>{{ $n->votes }}</b> ({{ $n->vote_share }}%)</div>
          <div>Final score: <b class="text-navy">{{ $n->final_score }}</b></div>
          @if($n->disqualified)<div class="text-red-600 font-700">Disqualified - {{ $n->disqualified_reason }}</div>@endif
        @endif
      </div>
    @endif
    @if($cycle->status === 'voting' && !$myVote && $n->staff_id != session('staff_id'))
      <form method="POST" action="{{ route('admin.eotm.vote') }}" class="mt-4" onsubmit="return confirm('Vote for {{ addslashes($n->name) }}? You cannot change it.')">
        @csrf <input type="hidden" name="ym" value="{{ $ym }}"><input type="hidden" name="nominee" value="{{ $n->staff_id }}">
        <button class="w-full bg-navy text-white font-display font-700 py-2 rounded-xl">Vote for {{ explode(' ', $n->name)[0] }}</button>
      </form>
    @elseif($cycle->status === 'voting' && $myVote == $n->staff_id)
      <div class="mt-4 text-center text-green-700 font-700 text-sm">✓ Your vote</div>
    @endif
  </div>
  @endforeach
</div>
@if($cycle->status === 'voting')
  <p class="text-xs text-gray-500 font-body mb-4">{{ $myVote ? 'You have voted.' : 'Each staff member votes once. You cannot vote for yourself.' }} {{ $votesCast }} of {{ $eligibleVoters }} staff have voted. Winner = 70% performance score + 30% staff vote.</p>
@endif
@endif

{{-- MANAGER CONTROLS --}}
@if($isMgr)
  @if($cycle->status === 'voting')
    <form method="POST" action="{{ route('admin.eotm.close') }}" class="mb-6" onsubmit="return confirm('Close voting and declare the winner?')">@csrf<input type="hidden" name="ym" value="{{ $ym }}">
      <button class="bg-gold text-navy font-display font-700 px-5 py-2.5 rounded-xl">Close voting &amp; declare winner</button></form>
  @endif

  @if($cycle->status === 'closed')
  <form method="POST" action="{{ route('admin.eotm.reward') }}" class="bg-white rounded-2xl border border-gray-200 p-5 mb-6 text-sm font-body max-w-2xl">
    @csrf <input type="hidden" name="ym" value="{{ $ym }}">
    <div class="font-display font-700 text-navy mb-2">Rewards for {{ $winner->name ?? 'the winner' }}</div>
    <div class="flex flex-wrap gap-4 mb-3">@foreach($rewardLabels as $k => $l)<label><input type="checkbox" name="rewards[]" value="{{ $k }}" {{ in_array($k, $rewards) ? 'checked' : '' }}> {{ $l }}</label>@endforeach</div>
    <div class="grid sm:grid-cols-3 gap-3">
      <label>Cash amount<input type="number" step="0.01" name="prize_amount" value="{{ $cycle->prize_amount }}" class="w-full border border-gray-200 rounded px-2 py-1"></label>
      <label>Currency<select name="prize_currency" class="w-full border border-gray-200 rounded px-2 py-1 bg-white">@foreach(['NGN','USD','GHS'] as $c)<option {{ $cycle->prize_currency===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></label>
      <label class="flex items-end gap-2"><input type="checkbox" name="paid" value="1" {{ $cycle->paid_at ? 'checked' : '' }}> Paid / given</label>
    </div>
    <label class="block mt-3">Note<input name="prize_note" value="{{ $cycle->prize_note }}" class="w-full border border-gray-200 rounded px-2 py-1" placeholder="e.g. voucher from Shoprite, salary incentive added to December pay"></label>
    <button class="mt-3 bg-navy text-white font-display font-700 px-5 py-2 rounded-xl">Save rewards</button>
  </form>
  @endif

  @if($cycle->status === 'rating' && $scores)
  <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
    <div class="font-display font-700 text-navy text-lg">Scores so far - {{ $monthLabel }}</div>
    <form method="POST" action="{{ route('admin.eotm.shortlist') }}" onsubmit="return confirm('Lock the scores and choose the top 3 nominees? Ratings close after this.')">@csrf<input type="hidden" name="ym" value="{{ $ym }}">
      <button class="bg-gold text-navy font-display font-700 px-5 py-2.5 rounded-xl">Choose top 3 &amp; open voting</button></form>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto mb-3">
    <table class="w-full text-xs font-body">
      <thead class="bg-gray-50 text-[10px] uppercase text-gray-500"><tr>
        <th class="text-left px-3 py-2">Staff</th>
        @foreach($criteria as $k => $c)<th class="px-1" title="{{ $c[2] }}">{{ $c[0] }}<div class="text-gray-400 normal-case">{{ $c[1] }}%</div></th>@endforeach
        <th>Score</th><th>Data</th><th class="text-left">Status</th></tr></thead>
      <tbody>
      @foreach($scores->sortByDesc('perf') as $x)
        <tr class="border-t border-gray-100 text-center {{ $x->eligible ? '' : 'bg-red-50/50' }}">
          <td class="px-3 py-2 text-left"><b class="text-navy">{{ $x->staff->name }}</b><div class="text-[10px] text-gray-400">{{ $x->staff->location }}</div></td>
          @foreach($criteria as $k => $c)<td class="{{ $x->scores[$k] === null ? 'text-gray-300' : '' }}">{{ $x->scores[$k] === null ? '–' : round($x->scores[$k]) }}</td>@endforeach
          <td class="font-700 text-navy text-sm">{{ $x->perf }}</td><td>{{ $x->coverage }}%</td>
          <td class="text-left px-2">@if($x->eligible)<span class="text-green-700 font-700">Eligible</span>@else<span class="text-red-600">{{ implode('; ', $x->reasons) }}</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <p class="text-[11px] text-gray-400 font-body mb-6">"–" means no data for that person this month; the other weights are scaled up, but a person needs at least 40% of the weight measured. Hover a column heading to see where it comes from.</p>
  @endif
@endif

@if($cycle->status === 'rating' && !$isMgr)
  <div class="bg-white rounded-2xl border border-gray-200 p-5 text-sm font-body text-gray-600 mb-6">Scores for {{ $monthLabel }} are being prepared. When the top 3 are chosen, voting opens here for every staff member.</div>
@endif

@if($history->isNotEmpty())
<h2 class="font-display font-700 text-navy text-lg mb-2">Past winners</h2>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100 max-w-2xl">
  @foreach($history as $h)
  <div class="flex items-center justify-between px-4 py-2.5 text-sm font-body">
    <span class="font-700 text-navy">{{ \Carbon\Carbon::parse($h->ym.'-01')->format('F Y') }}</span>
    <span>{{ $h->name }} <span class="text-xs text-gray-400">{{ $h->location }}</span></span>
    <a href="{{ route('admin.eotm.certificate', $h->id) }}" class="text-xs underline text-blue-700">Certificate</a>
  </div>
  @endforeach
</div>
@endif
@endsection
