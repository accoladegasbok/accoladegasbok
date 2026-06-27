{{-- FILE: resources/views/admin/customers/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Customers & Freelancers')
@section('page-title','Customers & Freelancers')
@section('page-sub','Auto-built from order/invoice history, plus manually-added contacts (freelancers, contractors, delivery, jobbers)')

@section('header-actions')
<a href="{{ route('admin.customers.contacts.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + Add Contact
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-5">
  <form method="GET" action="{{ route('admin.customers.index') }}" class="flex gap-3">
    <input type="text" name="q" value="{{ $search }}" placeholder="Search by name, phone, or type (Freelancer, Contractor...)"
      class="flex-1 border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    <button type="submit"
      class="bg-navy text-white font-display font-700 text-sm px-6 py-2.5 rounded-lg hover:bg-opacity-90 transition-colors">
      Search
    </button>
    @if($search)
    <a href="{{ route('admin.customers.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-4 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
      Clear
    </a>
    @endif
  </form>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Name</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Type</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Phone</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Orders</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Total Spent</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Last Purchase</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($customers as $c)
      <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 font-500 text-navy">{{ $c->name ?: '—' }}</td>
        <td class="px-4 py-3">
          @if($c->is_contact ?? false)
            <span class="badge badge-amber">{{ $c->contact_type }}</span>
          @else
            <span class="badge badge-blue">Customer</span>
          @endif
        </td>
        <td class="px-4 py-3 font-mono text-gray-600">{{ $c->phone }}</td>
        <td class="px-4 py-3 text-gray-500 text-xs">
          {{ collect([$c->city ?? null, $c->country ?? null])->filter()->join(', ') ?: '—' }}
        </td>
        <td class="px-4 py-3">
          <span class="badge badge-blue">{{ $c->total_orders }}</span>
        </td>
        <td class="px-4 py-3 font-display font-700 text-navy">${{ number_format($c->total_spent, 2) }}</td>
        <td class="px-4 py-3 text-gray-500 text-xs">{{ $c->last_purchase ? \Carbon\Carbon::parse($c->last_purchase)->format('M j, Y') : '—' }}</td>
        <td class="px-4 py-3 text-right">
          @if($c->is_contact ?? false)
            <a href="{{ route('admin.customers.contacts.edit', $c->id) }}" class="text-gold hover:text-yellow-600 font-body font-500 text-xs">Edit →</a>
          @else
            <a href="{{ route('admin.customers.show', $c->phone) }}" class="text-gold hover:text-yellow-600 font-body font-500 text-xs">View History →</a>
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">
          @if($search)
            No matches found for "{{ $search }}".
          @else
            No records yet — customers appear automatically as orders/invoices come in, or add a contact manually above.
          @endif
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

@if($lastPage > 1)
<div class="flex justify-center gap-2 mt-5">
  @for($p = 1; $p <= $lastPage; $p++)
    <a href="{{ route('admin.customers.index', ['q' => $search, 'page' => $p]) }}"
      class="px-3 py-1.5 rounded-lg text-sm {{ $p === $page ? 'bg-navy text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
      {{ $p }}
    </a>
  @endfor
</div>
@endif

@endsection
