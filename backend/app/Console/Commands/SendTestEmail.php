<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SampleEmail;

class SendTestEmail extends Command
{
    protected $signature = 'mail:send-test {to?} {--subject=} {--body=}';
    protected $description = 'Send a test email using the SampleEmail mailable';

    public function handle(): int
    {
        $to = $this->argument('to');
        $subject = $this->option('subject') ?? 'Test Email';
        $body = $this->option('body') ?? 'This is a sample email sent from artisan command.';

        if ($to) {
            Mail::to($to)->send(new SampleEmail($subject, $body));
            $this->info("Sent test email to {$to}");
        } else {
            $from = config('mail.from.address');
            Mail::to($from)->send(new SampleEmail($subject, $body));
            $this->info('Sent test email to configured from-address (or redirected by Mail::alwaysTo)');
        }

        return 0;
    }
}
