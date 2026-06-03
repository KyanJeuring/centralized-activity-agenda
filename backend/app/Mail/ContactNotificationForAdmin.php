<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactNotificationForAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public ContactRequest $contactRequest;
    public string $approveUrl;
    public string $rejectUrl;

    public function __construct(ContactRequest $contactRequest, string $approveUrl, string $rejectUrl)
    {
        $this->contactRequest = $contactRequest;
        $this->approveUrl = $approveUrl;
        $this->rejectUrl = $rejectUrl;
    }

    public function build()
    {
        return $this->subject('New club registration request')
            ->view('mail.admin.contact_request.html')
            ->text('mail.admin.contact_request.plain')
            ->with([
                'request' => $this->contactRequest,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
            ]);
    }
}
