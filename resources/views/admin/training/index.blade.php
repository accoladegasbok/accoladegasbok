{{-- FILE: resources/views/admin/training/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Staff Training')
@section('page-title', 'Staff Training')
@section('page-sub', 'Compliance board, courses and assignments. Red = overdue = cannot clock in')

@section('header-actions')
  <a href="{{ route('admin.training.assign') }}" class="bg-gold text-navy font-display font-700 text-xs px-3 py-2 rounded-xl">Assign training</a>
  @if($isMgr)<a href="{{ route('admin.training.create') }}" class="bg-navy text-white font-display font-700 text-xs px-3 py-2 rounded-xl">+ New course</a>@endif
@endsection

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<div class="flex gap-2 mb-4">
  @foreach(['board' => 'Compliance board', 'courses' => 'Courses'] as $k => $l)
    <a href="{{ route('admin.training.index', ['tab' => $k]) }}" class="px-4 py-1.5 rounded-full text-xs font-display font-700 border {{ $tab === $k ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">{{ $l }}</a>
  @endforeach
</div>

@if($tab === 'courses')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Course</th><th class="text-left">Provider</th><th>Length</th><th>Repeats</th><th>Pass</th><th>Done / assigned</th><th></th></tr></thead>
    <tbody>
    @forelse($courses as $c)
      <tr class="border-t border-gray-100 {{ $c->is_active ? '' : 'opacity-50' }}">
        <td class="px-4 py-2"><b class="text-navy">{{ $c->title }}</b><div class="text-[11px] text-gray-400">{{ $c->area }}</div></td>
        <td class="text-xs"><span class="px-2 py-0.5 rounded-full {{ $c->provider_type==='external' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">{{ ucfirst($c->provider_type) }}</span> {{ $c->provider_name }}</td>
        <td class="text-center">{{ $c->duration_minutes ? $c->duration_minutes.' min' : '—' }}</td>
        <td class="text-center">{{ $c->recurrence_days ? 'every '.$c->recurrence_days.' days' : 'once' }}</td>
        <td class="text-center">{{ $c->pass_mark }}%</td>
        <td class="text-center">{{ (int)$c->completed }} / {{ (int)$c->assigned }}</td>
        <td class="px-2 py-1 text-right whitespace-nowrap">
          <a class="text-xs underline text-blue-700" href="{{ route('admin.training.assign', ['course' => $c->id]) }}">Assign</a>
          @if($isMgr)
          · <a class="text-xs underline text-blue-700" href="{{ route('admin.training.edit', $c->id) }}">Edit</a>
          <form method="POST" action="{{ route('admin.training.toggle', $c->id) }}" class="inline">@csrf<button class="text-xs underline text-gray-600 ml-1">{{ $c->is_active ? 'Disable' : 'Enable' }}</button></form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No courses yet.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@else
<div class="space-y-2">
  @forelse($board as $x)
    @php $c = ['red'=>'border-red-500 bg-red-50','amber'=>'border-amber-400 bg-amber-50','green'=>'border-green-300 bg-white'][$x->state]; @endphp
    <div class="rounded-2xl border-2 {{ $c }} p-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3">
          <span class="inline-block w-3.5 h-3.5 rounded-full {{ ['red'=>'bg-red-600','amber'=>'bg-amber-400','green'=>'bg-green-500'][$x->state] }}"></span>
          <div><div class="font-display font-700 text-navy">{{ $x->staff->name }}</div><div class="text-[11px] text-gray-500 font-body">{{ $x->staff->location }} · {{ $x->staff->role }}</div></div>
          @if($x->state==='red')<span class="text-xs bg-red-600 text-white px-2 py-0.5 rounded-full font-700">OVERDUE - cannot clock in</span>@endif
        </div>
        <div class="text-xs text-gray-500 font-body">Completed: <b>{{ $x->done->n ?? 0 }}</b>@if($x->done) · last {{ \Carbon\Carbon::parse($x->done->last_at)->format('d M Y') }}@endif</div>
      </div>
      @if($x->open->isNotEmpty())
      <div class="mt-2 grid sm:grid-cols-2 gap-1">
        @foreach($x->open as $a)
          <div class="flex items-center justify-between text-xs font-body bg-white/70 rounded px-2 py-1">
            <span>{{ $a->title }} <span class="text-gray-400">due {{ \Carbon\Carbon::parse($a->due_date)->format('d M') }}</span> @if($a->is_retraining)<span class="text-purple-700">(retraining)</span>@endif</span>
            <span class="flex gap-2">
              @if($isMgr)<form method="POST" action="{{ route('admin.training.complete', $a->id) }}" onsubmit="return confirm('Mark as completed (external / classroom training)?')">@csrf<button class="text-green-700 underline">Mark done</button></form>@endif
              <form method="POST" action="{{ route('admin.training.assignment.delete', $a->id) }}" onsubmit="return confirm('Remove this assignment?')">@csrf @method('DELETE')<button class="text-red-600 underline">Remove</button></form>
            </span>
          </div>
        @endforeach
      </div>
      @endif
    </div>
  @empty
    <div class="text-gray-400 text-sm font-body">No staff found.</div>
  @endforelse
</div>
@endif
@endsection
