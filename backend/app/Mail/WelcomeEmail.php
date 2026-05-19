<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $welcomeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $welcomeUrl = null)
    {
        $this->name = $name;
        $this->welcomeUrl = $welcomeUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name'))
                    ->view('emails.welcome.html')
                    ->text('emails.welcome.plain')
                    ->with([
                        'name' => $this->name,
                        'welcomeUrl' => $this->welcomeUrl,
                    ]);
    }
}
