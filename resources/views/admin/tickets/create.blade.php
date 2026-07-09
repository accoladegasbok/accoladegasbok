{{-- FILE: resources/views/admin/tickets/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'New Ticket')
@section('page-title', 'Submit a Ticket')
@section('page-sub', 'Request an admin or manager action — delete invoice, edit price, approve discount etc.')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.tickets.store') }}">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif

        <div class="stat-card space-y-4">
            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Category *</label>
                <select name="category" required
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                    <option value="">Select a category...</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Subject *</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required
                       placeholder="Brief description of what you need..."
                       class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-yellow-400">
            </div>

            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Details</label>
                <textarea name="description" rows="4"
                          placeholder="Explain what you need and why, include relevant invoice/order numbers..."
                          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-yellow-400 resize-none">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Reference Type</label>
                    <select name="reference_type"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                        <option value="">None</option>
                        <option value="invoice">Invoice</option>
                        <option value="order">Order</option>
                        <option value="inventory">Inventory Part</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Reference ID / Number</label>
                    <input type="text" name="reference_id" value="{{ old('reference_id') }}"
                           placeholder="e.g. AZP-20240901-AB12"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-yellow-400">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">
                    Submit Ticket
                </button>
                <a href="{{ route('admin.tickets.index') }}"
                   class="border border-gray-200 text-gray-500 text-sm px-5 py-3 rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
