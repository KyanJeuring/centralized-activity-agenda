<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageClient extends Command
{
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

            return self::FAILURE;
        }

        $this->ensurePassportKeysReady();
        $this->ensurePersonalAccessClientExists();

        $this->info('Creating token for: '.$email);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'API Client',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $tokenResult = $user->createToken('Manual-Token');
        $token = $tokenResult->accessToken;

        $this->info('-----------------------------------------');
        $this->info('TOKEN GENERATED SUCCESSFULLY');
        $this->line($token);
        $this->info('-----------------------------------------');
        $this->warn("Give this string to the client. It is their 'Master Key'.");

        return self::SUCCESS;
    }

    private function ensurePersonalAccessClientExists(): void
    {
        $hasPersonalClient = DB::table('oauth_clients')
            ->where('provider', 'users')
            ->where('grant_types', 'like', '%personal_access%')
            ->exists();

        if ($hasPersonalClient) {
            return;
        }

        $this->call('passport:client', [
            '--personal' => true,
            '--name' => 'Personal Access Client',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }

    private function ensurePassportKeysReady(): void
    {
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (! file_exists($privateKeyPath) || ! file_exists($publicKeyPath)) {
            $this->call('passport:keys', ['--force' => true]);
        }

        @chmod($privateKeyPath, 0600);
        @chmod($publicKeyPath, 0600);
    }
}
