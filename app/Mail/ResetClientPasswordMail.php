<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetClientPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetCode;

    public function __construct($resetCode)
    {
        $this->resetCode = $resetCode;
    }

    public function build()
    {
        return $this->subject('بازیابی رمز عبور')
            ->view('emails.reset-client-password');
    }
}
