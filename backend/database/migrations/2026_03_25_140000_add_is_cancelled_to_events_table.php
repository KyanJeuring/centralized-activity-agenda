<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE events ADD COLUMN is_cancelled BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE events DROP COLUMN is_cancelled');
    }
};
