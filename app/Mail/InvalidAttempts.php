<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvalidAttempts extends Mailable
{
    use Queueable, SerializesModels;
    public $mailData = [];
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->mailData = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('eClinicAssist')
        ->from('noreply@eclinicassist.com', 'eClinicAssist')
        ->view('mail.InvalidAttempts');
    }
}
