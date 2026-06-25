{{-- FILE: resources/views/admin/staff/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Staff')
@section('page-title','Staff Management')
@section('page-sub','Manage who has access to the admin panel')

@section('header-actions')
<a href="{{ route('admin.staff.create') }}"
   class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors flex items-center gap-1.5">
  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
  Add Staff Member
</a>
@endsection

@section('content')

{{-- Summary cards --}}
<div class="grid grid-cols-3 gap-4 mb-6">
  <div class="stat-card text-center">
    <div class="font-display font-700 text-navy text-3xl">{{ $counts['total'] }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Total Staff</div>
  </div>
  <div class="stat-card text-center bg-green-50">
    <div class="font-display font-700 text-green-700 text-3xl">{{ $counts['active'] }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Active</div>
  </div>
  <div class="stat-card text-center bg-gray-50">
    <div class="font-display font-700 text-gray-500 text-3xl">{{ $counts['inactive'] }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Inactive</div>
  </div>
</div>

{{-- Staff table --}}
<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100 bg-gray-50">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Name</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Role</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Last Login</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($staff as $m)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors {{ !$m->is_active ? 'opacity-50' : '' }}" id="staff-row-{{ $m->id }}">

          {{-- Name + email --}}
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 font-display font-700 text-sm
                {{ $m->role === 'admin' ? 'bg-navy text-gold' : 'bg-gray-100 text-gray-600' }}">
                {{ strtoupper(substr($m->name, 0, 1)) }}
              </div>
              <div>
                <div class="font-500 text-navy">{{ $m->name }}
                  @if($m->id == session('staff_id'))
                    <span class="text-xs text-blue-500 font-body">(you)</span>
                  @endif
                </div>
                <div class="text-xs text-gray-400">{{ $m->email }}</div>
                @if($m->phone)
                  <div class="text-xs text-gray-400">{{ $m->phone }}</div>
                @endif
              </div>
            </div>
          </td>

          {{-- Role badge --}}
          <td class="px-4 py-3">
            <span class="badge
              @if($m->role==='admin') bg-navy text-gold border-navy
              @elseif($m->role==='manager') badge-blue
              @elseif($m->role==='supervisor') badge-blue
              @elseif($m->role==='staff') badge-green
              @elseif($m->role==='sales_rep') badge-amber
              @elseif($m->role==='stocking_clerk') badge-amber
              @else badge-gray @endif">
              {{ $roleLabels[$m->role] ?? ucwords(str_replace('_',' ',$m->role)) }}
            </span>
          </td>

          {{-- Location --}}
          <td class="px-4 py-3 text-xs text-gray-600">{{ $m->location }}</td>

          {{-- Last login --}}
          <td class="px-4 py-3 text-xs text-gray-400">
            {{ $m->last_login_at ? \Carbon\Carbon::parse($m->last_login_at)->diffForHumans() : 'Never' }}
          </td>

          {{-- Status --}}
          <td class="px-4 py-3">
            <span id="status-badge-{{ $m->id }}" class="badge {{ $m->is_active ? 'badge-green' : 'badge-gray' }}">
              {{ $m->is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>

          {{-- Actions --}}
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <a href="{{ route('admin.staff.edit', $m->id) }}"
                 class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
                Edit
              </a>
              @if($m->id != session('staff_id'))
              <button onclick="toggleStaff({{ $m->id }}, {{ $m->is_active ? 'true' : 'false' }}, this)"
                class="text-xs font-body border px-3 py-1.5 rounded-lg transition-colors
                  {{ $m->is_active
                    ? 'border-red-200 text-red-400 hover:border-red-400 hover:text-red-600'
                    : 'border-green-200 text-green-600 hover:border-green-400' }}">
                {{ $m->is_active ? 'Deactivate' : 'Reactivate' }}
              </button>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm font-body">No staff members found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Role legend --}}
<div class="mt-5 stat-card">
  <div class="text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-3">Role Permissions</div>
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-body">
    @foreach([
      'Admin'          => ['bg-navy text-gold', 'Everything — staff management, discount caps, commission terms, financial reports'],
      'Manager'        => ['badge-blue',         'Everything except staff management and discount/commission editing'],
      'Supervisor'     => ['badge-blue',         'Manager-level EXCEPT staff management, discount caps, and financial reports'],
      'Staff'          => ['badge-green',        'Harvest, inventory, invoices, customers'],
      'Sales Rep/Wholesaler' => ['badge-amber',        'Create invoices, earns commission on sales (set by admin)'],
      'Stocking Clerk' => ['badge-amber',        'New Harvest, Add Part Manually, Add Consumable ONLY — nothing else'],
      'Viewer'         => ['badge-gray',         'Read-only — dashboard and inventory list only'],
    ] as $role => [$cls, $desc])
    <div class="bg-gray-50 rounded-xl p-3">
      <span class="badge {{ $cls }} mb-2 inline-block">{{ $role }}</span>
      <div class="text-gray-500 leading-relaxed">{{ $desc }}</div>
    </div>
    @endforeach
  </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function toggleStaff(id, currentlyActive, btn) {
  const action = currentlyActive ? 'Deactivate' : 'Reactivate';
  if (!confirm(`${action} this staff member?`)) return;

  btn.disabled = true;
  btn.textContent = '...';

  const res  = await fetch(`/admin/staff/${id}/toggle`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
  });
  const data = await res.json();

  if (data.success) {
    const row   = document.getElementById('staff-row-' + id);
    const badge = document.getElementById('status-badge-' + id);

    if (data.is_active) {
      row.classList.remove('opacity-50');
      badge.textContent  = 'Active';
      badge.className    = 'badge badge-green';
      btn.textContent    = 'Deactivate';
      btn.className      = btn.className.replace('border-green-200 text-green-600 hover:border-green-400', 'border-red-200 text-red-400 hover:border-red-400 hover:text-red-600');
      btn.onclick        = () => toggleStaff(id, true, btn);
    } else {
      row.classList.add('opacity-50');
      badge.textContent  = 'Inactive';
      badge.className    = 'badge badge-gray';
      btn.textContent    = 'Reactivate';
      btn.className      = btn.className.replace('border-red-200 text-red-400 hover:border-red-400 hover:text-red-600', 'border-green-200 text-green-600 hover:border-green-400');
      btn.onclick        = () => toggleStaff(id, false, btn);
    }
    btn.disabled = false;
  } else {
    alert(data.error || 'Error. Please try again.');
    btn.disabled   = false;
    btn.textContent = currentlyActive ? 'Deactivate' : 'Reactivate';
  }
}
</script>
@endpush
