<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientRegistrationService
{
    public function registerClient(string $email, ?string $clubName = null): array
    {
        $email = trim($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address provided.');
        }

        if (User::where('email', $email)->exists()) {
            throw new \RuntimeException("Account already exists for {$email}");
        }

        $this->ensurePassportKeysReady();
        $this->ensurePersonalAccessClientExists();

        $clientName = $this->determineClientName($email, $clubName);

        $user = $this->createUser($email, $clientName);
        $club = $this->createClub($user, $clientName);
        $token = $this->createApiToken($user);

        $this->saveClubToken($club, $token);
        $emailSent = $this->sendWelcomeEmail($user, $club, $token);

        return [
            'user' => $user,
            'club' => $club,
            'token' => $token,
            'email_sent' => $emailSent,
        ];
    }

    private function createUser(string $email, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
        ]);
    }

    private function createClub(User $user, string $clubName): Club
    {
        $club = Club::updateOrCreate(
            ['owner_user_id' => $user->id],
            [
                'name' => $clubName,
                'source' => 'system, registration',
                'type' => 'remote',
            ]
        );

        if ($club->name !== $clubName) {
            $club->update(['name' => $clubName]);
            $club->refresh();
        }

        return $club;
    }

    private function determineClientName(string $email, ?string $clubName = null): string
    {
        if ($clubName !== null && trim($clubName) !== '') {
            return trim($clubName);
        }

        $username = Str::before($email, '@');

        return ucfirst($username) . ' Client';
    }

    private function createApiToken(User $user): string
    {
        return $user->createToken('API Access')->accessToken;
    }

    private function saveClubToken(Club $club, string $token): void
    {
        $club->update(['issued_key' => $token]);
    }

    private function sendWelcomeEmail(User $user, Club $club, string $token): bool
    {
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user, $club, $token));
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function ensurePassportKeysReady(): void
    {
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (! file_exists($privateKeyPath) || ! file_exists($publicKeyPath)) {
            $this->generatePassportKeys();
        }

        @chmod($privateKeyPath, 0600);
        @chmod($publicKeyPath, 0600);
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

        $this->generatePersonalAccessClient();
    }

    private function generatePassportKeys(): void
    {
        $this->runArtisanCommand('passport:keys', ['--force' => true]);
    }

    private function generatePersonalAccessClient(): void
    {
        $this->runArtisanCommand('passport:client', [
            '--personal' => true,
            '--name' => 'Personal Access Client',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }

    private function runArtisanCommand(string $command, array $parameters = []): void
    {
        Artisan::call($command, $parameters);
    }
}
