<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageScraperToken extends Command
{
    protected $signature = 'scraper:token {email=scraper@example.com}';
    protected $description = 'Create or rotate the scraper API token and mark the scraper club as scraped';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');

            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Scraper Service',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $tokenIds = DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        if (! empty($tokenIds)) {
            DB::table('oauth_refresh_tokens')
                ->whereIn('access_token_id', $tokenIds)
                ->delete();

            DB::table('oauth_access_tokens')
                ->whereIn('id', $tokenIds)
                ->delete();
        }

        $club = $user->club ?? Club::create([
            'id' => Str::uuid(),
            'name' => 'Scraper Club for '.$user->name,
            'source' => 'scraper',
            'type' => 'scraped',
            'owner_user_id' => $user->id,
        ]);

        $club->update([
            'type' => 'scraped',
            'source' => 'scraper',
        ]);

        $tokenResult = $user->createToken('Scraper API Token');
        $this->info('New scraper token:');
        $this->line($tokenResult->accessToken);

        return self::SUCCESS;
    }
}