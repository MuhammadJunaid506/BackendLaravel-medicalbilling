<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityFormLinkEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $emailData;
    public $subject = "";
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailData)
    {
        //
        $this->emailData = $emailData;
        $this->subject = $emailData['subject'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
        ->subject($this->subject)
        ->from('noreply@eclinicassist.com', 'eClinicAssist')
        ->view('mail.facility-form-link');
    }
}
