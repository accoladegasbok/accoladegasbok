<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $purpose;

    public function __construct(string $code, string $purpose)
    {
        $this->code    = $code;
        $this->purpose = $purpose;
    }

    public function build()
    {
        $subjects = [
            'register'      => 'Verify your Auto Zenith Parts account',
            'login'         => 'Your Auto Zenith Parts login code',
            'change_email'  => 'Confirm your new email address',
            'change_phone'  => 'Confirm your new phone number',
            'telegram_link' => 'Link your Telegram account',
        ];

        return $this->subject($subjects[$this->purpose] ?? 'Your Auto Zenith Parts verification code')
            ->view('emails.customer-otp');
    }
}
