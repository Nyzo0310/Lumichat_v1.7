<?php

// database/migrations/2025_09_07_000001_adjust_appointment_unique_index.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop old unique index on (counselor_id, scheduled_at)
        //    If your index name differs, adjust it. You can check via:
        //    SHOW INDEX FROM tbl_appointments;
        DB::statement("ALTER TABLE tbl_appointments DROP INDEX uniq_counselor_datetime");

        // 2) Add a stored generated column that is 1 for blocking statuses
        DB::statement("
            ALTER TABLE tbl_appointments
            ADD COLUMN is_blocking TINYINT(1)
            AS (CASE WHEN status IN ('pending','confirmed','completed') THEN 1 ELSE 0 END) STORED
        ");

        // 3) New unique index considers the blocking flag
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

