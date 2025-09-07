<?php

// database/migrations/2025_09_07_000001_adjust_appointment_unique_index.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop old unique index (name may differ in your DB — adjust if needed)
        DB::statement("ALTER TABLE tbl_appointments DROP INDEX uniq_counselor_datetime");

        // Add a generated column: 1 if status is blocking, else 0
        DB::statement("
            ALTER TABLE tbl_appointments
            ADD COLUMN is_blocking TINYINT(1)
            AS (CASE WHEN status IN ('pending','confirmed','completed') THEN 1 ELSE 0 END) STORED
        ");

        // New unique key includes the flag
        DB::statement("
            ALTER TABLE tbl_appointments
            ADD UNIQUE INDEX uniq_counselor_datetime_blocking (counselor_id, scheduled_at, is_blocking)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tbl_appointments DROP INDEX uniq_counselor_datetime_blocking");
        DB::statement("ALTER TABLE tbl_appointments DROP COLUMN is_blocking");
        DB::statement("ALTER TABLE tbl_appointments ADD UNIQUE INDEX uniq_counselor_datetime (counselor_id, scheduled_at)");
    }
};
