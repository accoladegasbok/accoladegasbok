{{-- FILE: resources/views/admin/invoices/invoice-pdf.blade.php
     The downloaded / emailed PDF. It renders the SAME document body as the printed customer copy
     (admin/invoices/_document.blade.php + _document-styles.blade.php), so the two can no longer differ.
     The only intended difference: the QR code is drawn on screen only.
     DejaVu Sans is bundled with DomPDF and contains the naira sign, so the PDF shows the real
     currency symbol. If a naira sign ever prints as a box, tell the developer and switch the PDF to "NGN". --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $invoiceNo ?? 'Document' }}</title>
<style>
    @page { margin: 28px 30px; }
    body { margin: 0; font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; }
@include('admin.invoices._document-styles')
</style>
</head>
<body>
@php $docMode = 'pdf'; $copyKey = 'customer'; @endphp
@include('admin.invoices._document', ['copyKey' => 'customer', 'docMode' => 'pdf'])
</body>
</html>
