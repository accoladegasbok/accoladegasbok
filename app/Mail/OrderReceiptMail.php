<?php
// FILE: app/Mail/OrderReceiptMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $items;
    public $receiptUrl;

    public function __construct($order, $items, string $receiptUrl)
    {
        $this->order      = $order;
        $this->items      = $items;
        $this->receiptUrl = $receiptUrl;
    }

    public function build()
    {
        return $this->subject("Your Receipt — Order {$this->order->order_ref} — Auto Zenith Parts")
            ->view('emails.order-receipt')
            ->with([
                'order'      => $this->order,
                'items'      => $this->items,
                'receiptUrl' => $this->receiptUrl,
            ]);
    }
}
