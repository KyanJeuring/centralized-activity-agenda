<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ManageClient extends Command
{
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $email = $this->argument('email');
    $this->info("Creating token for: " . $email);

    // 1. Find or create the user in your SQLite DB
    $user = \App\Models\User::firstOrCreate(
        ['email' => $email],
        [
            'name' => 'API Client', 
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32))
        ]
    );

    // 2. Generate the Long-Lived Personal Access Token
    // Note: If this line errors, we might need one more small Passport fix
    $tokenResult = $user->createToken('Manual-Token');
    $token = $tokenResult->accessToken;

    $this->info("-----------------------------------------");
    $this->info("TOKEN GENERATED SUCCESSFULLY");
    $this->line($token); 
    $this->info("-----------------------------------------");
    $this->warn("Give this string to the client. It is their 'Master Key'.");
}
}
