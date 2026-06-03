<?php

namespace App\Console\Commands;

use App\Services\ClientRegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManageClient extends Command
{
    public function __construct(private ClientRegistrationService $registrationService)
    {
        parent::__construct();
    }

    // Example: php artisan client:manage api.client@example.com

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:manage {email} {--action=add}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or fetch an API client user and generate a Passport token';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');

            Log::warning('Client registration rejected due to invalid email', [
                'type' => 'validation',
                'email' => $email,
            ]);

            return self::FAILURE;
        }

        $this->info('Creating token for: '.$email);

        try {
            $result = $this->registrationService->registerClient($email);
        } catch (Throwable $exception) {
            Log::critical('Client registration failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'email' => $email,
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        Log::info('Client registration completed', [
            'type' => 'registration',
            'email' => $email,
            'user_id' => $result['user']->id,
            'club_id' => $result['club']->id,
            'email_sent' => $result['email_sent'],
        ]);

        $this->info('Client registration completed successfully.');
        $this->info('Email sent: '.($result['email_sent'] ? 'yes' : 'no'));
        $this->info('User ID: '.$result['user']->id);
        $this->info('Club ID: '.$result['club']->id);

        $this->info('-----------------------------------------');
        $this->info('TOKEN GENERATED SUCCESSFULLY');
        $this->line($result['token']);
        $this->info('-----------------------------------------');
        $this->warn("Give this string to the client. It is their 'Master Key'.");

        return self::SUCCESS;
    }
}