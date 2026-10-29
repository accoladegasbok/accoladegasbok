{{-- FILE: resources/views/partials/_payments-admin.blade.php
     Admin-only panel shown on the Edit Order / Edit Invoice pages: unapprove or delete payments.
     Usage: @include('partials._payments-admin', ['kind' => 'order'|'invoice', 'docId' => $id]) --}}
@if(session('staff_role') === 'admin')
@php
    $__isOrder = ($kind ?? 'order') === 'order';
    if ($__isOrder) {
        $__sum  = \App\Http\Controllers\Admin\OrderAdminController::paymentSummary((int) $docId);
        $__cc   = $__sum['currencyCode'] ?? 'NGN';
    } else {
        $__sum  = \App\Http\Controllers\Admin\InvoiceController::invoicePaymentSummary((int) $docId);
        $__cc   = \Illuminate\Support\Facades\DB::table('invoices')->where('id', $docId)->value('currency_code') ?: 'NGN';
    }
    $__sym = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'][$__cc] ?? '';
    $__fmt = fn ($n) => $__sym . number_format((float) $n, $__cc === 'NGN' ? 0 : 2);
@endphp
<div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; margin:20px 0;">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
        <div>
            <div style="font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:.04em; color:#0d1b2a;">Payments (admin only)</div>
            <div style="font-size:12px; color:#6b7280;">Unapprove a confirmed payment to put it back to Pending, or delete a payment record. The balance and any overpayment credit update automatically.</div>
        </div>
        <div style="font-size:12px; color:#374151; text-align:right;">
            Confirmed paid: <strong>{{ $__fmt($__sum['confirmedPaid']) }}</strong><br>
            Balance due: <strong>{{ $__fmt($__sum['balanceDue']) }}</strong>
        </div>
    </div>
    @if($__sum['payments']->count())
    <table style="width:100%; font-size:12px; border-collapse:collapse;">
        <thead><tr style="background:#f5f5f5; color:#999; text-transform:uppercase;">
            <th style="padding:6px; text-align:left;">Amount</th><th style="padding:6px; text-align:left;">Method</th>
            <th style="padding:6px; text-align:left;">Status</th><th style="padding:6px; text-align:left;">Recorded</th><th></th>
        </tr></thead>
        <tbody>
        @foreach($__sum['payments'] as $__p)
        <tr style="border-top:1px solid #eee;">
            <td style="padding:6px; font-weight:700;">{{ $__fmt($__p->amount_local) }}</td>
            <td style="padding:6px;">{{ $__p->payment_method }}</td>
            <td style="padding:6px;">{{ ucfirst($__p->status) }}</td>
            <td style="padding:6px; color:#6b7280;">{{ \Illuminate\Support\Carbon::parse($__p->created_at)->format('M j, Y') }}</td>
            <td style="padding:6px; text-align:right; white-space:nowrap;">
                @if($__p->status === 'confirmed')
                <form method="POST" action="{{ route($__isOrder ? 'admin.orders.payments.unconfirm' : 'admin.invoices.payments.unconfirm', [$docId, $__p->id]) }}" style="display:inline;" onsubmit="return confirm('Unapprove this payment? It goes back to Pending and the balance goes back up.')">@csrf<button style="color:#b7791f; border:none; background:none; cursor:pointer; font-size:12px;">↩ Unapprove</button></form>
                @endif
                <form method="POST" action="{{ route($__isOrder ? 'admin.orders.payments.delete' : 'admin.invoices.payments.delete', [$docId, $__p->id]) }}" style="display:inline;" onsubmit="return confirm('Delete this payment record permanently?')">@csrf<button style="color:#c0392b; border:none; background:none; cursor:pointer; font-size:12px;">🗑 Delete</button></form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div style="font-size:12px; color:#9ca3af;">No payments recorded yet.</div>
    @endif
</div>
@endif
