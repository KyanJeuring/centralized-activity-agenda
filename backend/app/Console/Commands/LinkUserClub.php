<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LinkUserClub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club:link-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        try {
            $club = \App\Models\Club::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => $user->name,
                'source' => '',
                'type' => 'remote',
                'owner_user_id' => $user->id
            ]);
        } catch (Throwable $exception) {
            Log::critical('User club linking failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'email' => $email,
                'user_id' => $user->id,
            ]);

            $this->error('Unable to create the club. Check the application logs.');

            return 1;
        }

        $this->info("Successfully linked user {$email} to new club: {$club->name} (ID: {$club->id})");
        Log::info('User club linked successfully', [
            'type' => 'registration',
            'email' => $email,
            'user_id' => $user->id,
            'club_id' => $club->id,
        ]);
        
        $this->info("Successfully linked user {$email} to new club: {$club->name} (ID: {$club->id})");
        return 0;
    }
}
