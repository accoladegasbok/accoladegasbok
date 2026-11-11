{{-- FILE: resources/views/admin/training/assign.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Assign Training')
@section('page-title', 'Assign Training')
@section('page-sub', 'Pick a course, the staff and a due date. Overdue = red and unable to clock in')

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.training.assign.store') }}" class="space-y-5 text-sm font-body max-w-4xl">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 p-5 grid sm:grid-cols-2 gap-4">
    <label>Course
      <select name="course_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
        @foreach($courses as $c)<option value="{{ $c->id }}" {{ $pick===$c->id?'selected':'' }}>{{ $c->title }} ({{ $c->provider_type }}{{ $c->duration_minutes ? ', '.$c->duration_minutes.' min' : '' }})</option>@endforeach
      </select></label>
    <label>Due date<input type="date" name="due_date" min="{{ now()->toDateString() }}" value="{{ now()->addDays(14)->toDateString() }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-3"><div class="font-display font-700 text-navy">Staff</div>
      <label class="text-xs"><input type="checkbox" id="all"> Select everyone</label></div>
    @foreach($staff as $loc => $list)
      <div class="mb-3"><div class="text-[11px] uppercase tracking-wider text-gray-400 mb-1">{{ $loc ?: 'No location' }}
        <button type="button" class="ml-2 underline text-blue-700 grp">select all</button></div>
        <div class="grid sm:grid-cols-3 gap-1">@foreach($list as $s)<label class="text-sm"><input type="checkbox" name="staff_ids[]" value="{{ $s->id }}" class="sx"> {{ $s->name }} <span class="text-[10px] text-gray-400">{{ $s->role }}</span></label>@endforeach</div></div>
    @endforeach
  </div>
  <button class="bg-gold text-navy font-display font-700 px-6 py-2.5 rounded-xl">Assign</button>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('all').onchange = e => document.querySelectorAll('.sx').forEach(c => c.checked = e.target.checked);
document.querySelectorAll('.grp').forEach(b => b.onclick = () => b.closest('div.mb-3').querySelectorAll('.sx').forEach(c => c.checked = true));
</script>
@endpush
