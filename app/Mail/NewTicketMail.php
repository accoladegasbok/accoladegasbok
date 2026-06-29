<?php
// FILE: app/Mail/NewTicketMail.php
//
// Requires MAIL_* settings in .env (same as the existing
// OrderReceiptMail already in this project) to actually send.

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function build()
    {
        return $this->subject("New Staff Ticket: {$this->ticket->ticket_no} — {$this->ticket->subject}")
            ->view('emails.new-ticket');
    }
}
