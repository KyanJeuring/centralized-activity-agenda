<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SampleEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;

    public function __construct($subject = 'Test Email', $body = 'This is a sample email.')
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.sample.html')
                    ->text('emails.sample.plain')
                    ->with(['body' => $this->body]);
    }
}
