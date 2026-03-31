<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $unownedCount = DB::table('clubs')->whereNull('owner_user_id')->count();

        if ($unownedCount > 0) {
            throw new RuntimeException("Cannot enforce NOT NULL on clubs.owner_user_id, {$unownedCount} clubs are still unowned.");
        }

        DB::statement('ALTER TABLE clubs ALTER COLUMN owner_user_id SET NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clubs ALTER COLUMN owner_user_id DROP NOT NULL');
    }
};
