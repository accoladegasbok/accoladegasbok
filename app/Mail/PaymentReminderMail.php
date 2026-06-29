<?php
// FILE: app/Mail/PaymentReminderMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $balanceDue;

    public function __construct($order, $balanceDue)
    {
        $this->order = $order;
        $this->balanceDue = $balanceDue;
    }

    public function build()
    {
        return $this->subject("Payment Reminder — Order {$this->order->order_ref}")
            ->view('emails.payment-reminder');
    }
}
