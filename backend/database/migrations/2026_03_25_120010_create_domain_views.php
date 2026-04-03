<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $viewsPath = base_path('database_sql/views.sql');

        if (! File::exists($viewsPath)) {
            throw new RuntimeException("SQL file not found: {$viewsPath}");
        }

        DB::unprepared(File::get($viewsPath));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP VIEW IF EXISTS vw_events_today;
            DROP VIEW IF EXISTS vw_events_undated;
            DROP VIEW IF EXISTS vw_events_upcoming;
            DROP VIEW IF EXISTS vw_events_past;
            DROP VIEW IF EXISTS vw_event_details;
            DROP VIEW IF EXISTS vw_events_all;
        SQL);
    }
};
