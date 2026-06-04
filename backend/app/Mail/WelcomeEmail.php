<?php

namespace App\Mail;

use App\Models\Club;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Club $club;
    public string $token;

    public function __construct(User $user, Club $club, string $token)
    {
        $this->user = $user;
        $this->club = $club;
        $this->token = $token;
    }

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name'))
                    ->view('mail.welcome.html')
                    ->text('mail.welcome.plain')
                    ->with([
                        'user' => $this->user,
                        'club' => $this->club,
                        'token' => $this->token,
                    ]);
    }
}
