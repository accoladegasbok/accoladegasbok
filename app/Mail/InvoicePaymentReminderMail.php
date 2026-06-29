<?php
// FILE: app/Mail/InvoicePaymentReminderMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $balanceDue;
    public $balanceFmt;

    public function __construct($invoice, $balanceDue, $balanceFmt)
    {
        $this->invoice = $invoice;
        $this->balanceDue = $balanceDue;
        $this->balanceFmt = $balanceFmt;
    }

    public function build()
    {
        return $this->subject("Payment Reminder — Invoice {$this->invoice->invoice_no}")
            ->view('emails.invoice-payment-reminder');
    }
}
