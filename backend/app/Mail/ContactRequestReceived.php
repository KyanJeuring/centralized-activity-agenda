<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public ContactRequest $contactRequest;

    public function __construct(ContactRequest $contactRequest)
    {
        $this->contactRequest = $contactRequest;
    }

    public function build()
    {
        return $this->subject('Your registration request has been received')
            ->view('mail.contact.received.html')
            ->text('mail.contact.received.plain')
            ->with([
                'request' => $this->contactRequest,
            ]);
    }
}
