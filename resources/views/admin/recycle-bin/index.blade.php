{{-- FILE: resources/views/admin/recycle-bin/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Recycle Bin')
@section('page-title', 'Recycle Bin')
@section('page-sub', 'Soft-deleted invoices and orders — restore or permanently delete')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5 text-sm text-green-700">{{ session('success') }}</div>
@endif

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-5 py-4 bg-red-50 border-b border-red-100 flex items-center justify-between">
        <div>
            <h3 class="font-display font-700 text-red-700 text-sm uppercase tracking-wide">🗑 Recycle Bin</h3>
            <p class="text-xs text-red-500 mt-0.5">Items here are hidden from normal views. Restore to bring back, or permanently delete to remove forever.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="bulkRestore()" class="text-xs border border-green-300 text-green-700 rounded-lg px-3 py-1.5 hover:bg-green-50 font-600">↩ Restore Selected</button>
            @if(Session::get('staff_role') === 'admin')
            <button onclick="bulkDelete()" class="text-xs border border-red-300 text-red-600 rounded-lg px-3 py-1.5 hover:bg-red-50 font-600">✕ Delete Selected Forever</button>
            @endif
        </div>
    </div>

    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left w-8"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase tracking-wide">Ref</th>
                <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase tracking-wide">Customer</th>
                <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase tracking-wide">Type</th>
                <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase tracking-wide">Amount</th>
                <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase tracking-wide">Deleted</th>
                <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase tracking-wide">By</th>
                <th class="px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($deletedItems as $item)
            @php
                $sym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$item->currency_code ?? 'NGN'] ?? '₦';
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <input type="checkbox" class="row-check" data-type="{{ $item->type }}" data-id="{{ $item->id }}">
                </td>
                <td class="px-4 py-3 font-mono text-xs text-navy font-700">{{ $item->ref }}</td>
                <td class="px-4 py-3">
                    <div class="text-xs font-600 text-gray-800">{{ $item->customer_name }}</div>
                    <div class="text-[10px] text-gray-400">{{ $item->customer_phone }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                        {{ $item->type === 'invoice' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $item->label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-700 text-sm text-gold">
                    {{ $sym }}{{ number_format($item->amount_local) }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                    {{ \Carbon\Carbon::parse($item->deleted_at)->format('d M Y H:i') }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">{{ $item->deleted_by_name }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                        <form method="POST" action="{{ route('admin.recycle-bin.restore', [$item->type, $item->id]) }}">
                            @csrf
                            <button class="text-xs border border-green-200 text-green-600 rounded-lg px-3 py-1 hover:bg-green-50 transition-colors">
                                Restore
                            </button>
                        </form>
                        @if(Session::get('staff_role') === 'admin')
                        <form method="POST" action="{{ route('admin.recycle-bin.destroy', [$item->type, $item->id]) }}"
                              onsubmit="return confirm('Permanently delete {{ $item->ref }}? This CANNOT be undone.')">
                            @csrf @method('DELETE')
                            <button class="text-xs border border-red-200 text-red-500 rounded-lg px-3 py-1 hover:bg-red-50 transition-colors">
                                Delete Forever
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-16 text-center text-gray-400 text-sm">
                    🎉 Recycle bin is empty — nothing has been deleted.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-4 py-3 border-t border-gray-100">
        {{ $deletedItems->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
}

function getSelected() {
    return Array.from(document.querySelectorAll('.row-check:checked')).map(c => ({
        type: c.dataset.type, id: parseInt(c.dataset.id)
    }));
}

async function bulkRestore() {
    const items = getSelected();
    if (!items.length) { alert('Select at least one item.'); return; }
    if (!confirm(`Restore ${items.length} item(s)?`)) return;
    const res = await fetch('{{ route('admin.recycle-bin.bulk-restore') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ items })
    });
    const data = await res.json();
    if (data.success) { location.reload(); } else { alert(data.error || 'Failed.'); }
}

async function bulkDelete() {
    const items = getSelected();
    if (!items.length) { alert('Select at least one item.'); return; }
    if (!confirm(`Permanently delete ${items.length} item(s)? This CANNOT be undone.`)) return;
    const res = await fetch('{{ route('admin.recycle-bin.bulk-force-delete') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ items })
    });
    const data = await res.json();
    if (data.success) { location.reload(); } else { alert(data.error || 'Failed.'); }
}
</script>
@endpush
