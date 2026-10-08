{{-- FILE: resources/views/admin/subscribers/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Email Subscribers')
@section('page-title', 'Email Subscribers')
@section('page-sub', 'People who asked on the website to get our emails')

@section('content')
<div class="flex flex-wrap items-center gap-3 mb-4">
  <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-body"><span class="font-display font-700 text-navy text-lg">{{ number_format($active) }}</span> subscribed</div>
  <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-body text-gray-500">{{ number_format($total - $active) }} unsubscribed</div>
  <a href="{{ route('admin.subscribers.export') }}" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-5 py-2.5 rounded-lg transition-colors">Download CSV (subscribed only)</a>
  <form method="GET" class="ml-auto">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search email..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-64 focus:outline-none focus:border-gold">
  </form>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
      <tr><th class="text-left px-4 py-3">Email</th><th class="text-left px-4 py-3">From</th><th class="text-left px-4 py-3">Subscribed</th><th class="text-left px-4 py-3">Status</th></tr>
    </thead>
    <tbody>
      @forelse($subscribers as $s)
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2.5">{{ $s->email }}</td>
        <td class="px-4 py-2.5 text-gray-500">{{ $s->source }}</td>
        <td class="px-4 py-2.5 text-gray-500">{{ $s->subscribed_at ? \Carbon\Carbon::parse($s->subscribed_at)->format('d M Y, H:i') : '—' }}</td>
        <td class="px-4 py-2.5">
          @if($s->unsubscribed_at)<span class="badge badge-gray">Unsubscribed</span>@else<span class="badge badge-green">Subscribed</span>@endif
        </td>
      </tr>
      @empty
      <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No subscribers yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
