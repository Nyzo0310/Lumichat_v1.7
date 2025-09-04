<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // If the table doesn't exist in your env, create it minimal
        if (!Schema::hasTable('tbl_appointments')) {
            Schema::create('tbl_appointments', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('student_id');
                $t->unsignedBigInteger('counselor_id');
                $t->dateTime('scheduled_at');
                $t->enum('status', ['pending','confirmed','canceled','completed'])->default('pending');
                $t->longText('final_note')->nullable();
                $t->unsignedBigInteger('finalized_by')->nullable();
                $t->dateTime('finalized_at')->nullable();
                $t->timestamps();
            });
            return;
        }

        Schema::table('tbl_appointments', function (Blueprint $t) {
            if (!Schema::hasColumn('tbl_appointments', 'final_note')) {
                $t->longText('final_note')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tbl_appointments', 'finalized_by')) {
                $t->unsignedBigInteger('finalized_by')->nullable()->after('final_note');
            }
            if (!Schema::hasColumn('tbl_appointments', 'finalized_at')) {
                $t->dateTime('finalized_at')->nullable()->after('finalized_by');
            }
        });

        // Safely drop legacy 'notes' if it still exists
        if (Schema::hasColumn('tbl_appointments', 'notes')) {
            Schema::table('tbl_appointments', function (Blueprint $t) {
                $t->dropColumn('notes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tbl_appointments', function (Blueprint $t) {
            if (Schema::hasColumn('tbl_appointments', 'final_note')) {
                $t->dropColumn('final_note');
            }
            if (Schema::hasColumn('tbl_appointments', 'finalized_by')) {
                $t->dropColumn('finalized_by');
            }
            if (Schema::hasColumn('tbl_appointments', 'finalized_at')) {
                $t->dropColumn('finalized_at');
            }
            // You can re-add legacy notes if you want:
            // $t->longText('notes')->nullable();
        });
    }
};
