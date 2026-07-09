{{-- FILE: resources/views/admin/override/logs.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Override Logs')
@section('page-title', 'Override Audit Log')
@section('page-sub', 'Every supervisor PIN use — successful and failed — is recorded here')

@section('content')

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <table class="w-full">
        <thead class="bg-navy text-white">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Action</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Context</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Approved By</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Role</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Requested By</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Date / Time</th>
                <th class="px-4 py-3 text-xs font-display uppercase tracking-wide">Result</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($logs as $log)
            @php $failed = str_contains($log->context ?? '', '[FAILED'); @endphp
            <tr class="hover:bg-gray-50 {{ $failed ? 'bg-red-50/50' : '' }}">
                <td class="px-4 py-3 font-600 text-sm text-navy">{{ $log->action }}</td>
                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs">
                    {{ $log->context ? Str::limit(str_replace(' [FAILED — invalid PIN]', '', $log->context), 80) : '—' }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-700">{{ $log->approver_name ?? ($failed ? 'Unknown' : '—') }}</td>
                <td class="px-4 py-3">
                    @if(!$failed)
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700 bg-blue-100 text-blue-700 capitalize">{{ $log->approved_by_role }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">{{ $log->requester_name ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</td>
                <td class="px-4 py-3 text-center">
                    @if($failed)
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-700 bg-red-100 text-red-700">✕ Failed</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-700 bg-green-100 text-green-700">✓ Approved</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No override activity recorded yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
</div>

@endsection
