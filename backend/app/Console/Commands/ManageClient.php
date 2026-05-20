<?php

namespace App\Console\Commands;

use App\Services\ClientRegistrationService;
use Illuminate\Console\Command;

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

        try {
            $result = $this->registrationService->registerClient($email);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Client registration completed successfully.');
        $this->info('Email sent: ' . ($result['email_sent'] ? 'yes' : 'no'));
        $this->info('User ID: ' . $result['user']->id);
        $this->info('Club ID: ' . $result['club']->id);
        $this->info('Token:');
        $this->line($result['token']);

        return self::SUCCESS;
    }
}
