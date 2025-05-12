<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractAndDiscoveryDocument extends Mailable
{
    use Queueable, SerializesModels;
    public $emailData = [];
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        //
        $this->emailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Eclinic Assist')
        ->from('noreply@eclinicassist.com', 'Eclinic Assist')
        ->view('mail.contract-discovery-document');
    }
}
