<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public ContactRequest $contactRequest;

    public function __construct(ContactRequest $contactRequest)
    {
        $this->contactRequest = $contactRequest;
    }

    public function build()
    {
        return $this->subject('Your registration request has been declined')
            ->view('mail.contact.rejected.html')
            ->text('mail.contact.rejected.plain')
            ->with([
                'request' => $this->contactRequest,
            ]);
    }
}
