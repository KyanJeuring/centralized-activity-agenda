<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $clubs = DB::table('clubs')
            ->whereNull('owner_user_id')
            ->get(['id', 'name']);

        foreach ($clubs as $club) {
            $syntheticEmail = 'club+'.str_replace('-', '', (string) $club->id).'@club.local';

            $userId = DB::table('users')
                ->where('email', $syntheticEmail)
                ->value('id');

            if (! $userId) {
                $now = now();

                $userId = DB::table('users')->insertGetId([
                    'name' => $club->name ?: 'Club Owner',
                    'email' => $syntheticEmail,
                    'password' => Hash::make('club-owner-password-rotate-me'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('clubs')
                ->where('id', $club->id)
                ->update(['owner_user_id' => $userId]);
        }
    }

    public function down(): void
    {
        DB::table('clubs')->update(['owner_user_id' => null]);
    }
};
