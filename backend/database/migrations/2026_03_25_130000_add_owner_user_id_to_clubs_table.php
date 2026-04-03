<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE clubs
            ADD COLUMN IF NOT EXISTS owner_user_id BIGINT;

            CREATE INDEX IF NOT EXISTS clubs_owner_user_id_idx
            ON clubs(owner_user_id);

            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'clubs_owner_user_id_foreign'
                ) THEN
                    ALTER TABLE clubs
                    ADD CONSTRAINT clubs_owner_user_id_foreign
                    FOREIGN KEY (owner_user_id) REFERENCES users(id);
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE clubs
            DROP CONSTRAINT IF EXISTS clubs_owner_user_id_foreign;

            DROP INDEX IF EXISTS clubs_owner_user_id_idx;

            ALTER TABLE clubs
            DROP COLUMN IF EXISTS owner_user_id;
        SQL);
    }
};
