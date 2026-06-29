{{-- FILE: resources/views/emails/new-ticket.blade.php --}}
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222; max-width: 600px; margin: 0 auto;">
  <div style="background: #0A1F5C; color: #fff; padding: 20px; border-radius: 8px 8px 0 0;">
    <h2 style="margin:0;">New Staff Ticket — {{ $ticket->ticket_no }}</h2>
  </div>
  <div style="border: 1px solid #e2e8f0; border-top: none; padding: 20px; border-radius: 0 0 8px 8px;">
    <p><strong>Category:</strong> {{ $ticket->category }}</p>
    <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
    <p><strong>Raised by:</strong> {{ $ticket->raised_by_name ?? 'Staff' }}</p>
    @if($ticket->description)
    <p><strong>Description:</strong><br>{{ $ticket->description }}</p>
    @endif
    <p style="margin-top: 24px;">
      <a href="{{ url('/admin/tickets/' . $ticket->id) }}" style="background:#C8960C; color:#0A1F5C; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:bold;">
        View & Respond
      </a>
    </p>
  </div>
</body>
</html>
