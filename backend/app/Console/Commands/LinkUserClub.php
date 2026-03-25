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

        $club = \App\Models\Club::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Demo Club for ' . $user->name,
            'source' => 'system',
            'type' => 'remote',
            'owner_user_id' => $user->id
        ]);
        
        $this->info("Successfully linked user {$email} to new club: {$club->name} (ID: {$club->id})");
        return 0;
    }
}
