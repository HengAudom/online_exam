<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix tblgroup StartDate and EndDate
        if (Schema::hasTable('tblgroup')) {
            Schema::table('tblgroup', function (Blueprint $table) {
                if (!Schema::hasColumn('tblgroup', 'StartDate')) {
                    $table->date('StartDate')->nullable()->after('GroupName');
                }
                if (!Schema::hasColumn('tblgroup', 'EndDate')) {
                    $table->date('EndDate')->nullable()->after('StartDate');
                }
            });
        }

        // 2. Make UserId nullable in tblstudent and tbladmin
        try {
            if (Schema::hasTable('tblstudent') && Schema::hasColumn('tblstudent', 'UserId')) {
                DB::statement("ALTER TABLE tblstudent MODIFY COLUMN UserId BIGINT UNSIGNED NULL DEFAULT NULL");
            }
            if (Schema::hasTable('tbladmin')) {
                if (Schema::hasColumn('tbladmin', 'UserId')) {
                    DB::statement("ALTER TABLE tbladmin MODIFY COLUMN UserId BIGINT UNSIGNED NULL DEFAULT NULL");
                }
                if (Schema::hasColumn('tbladmin', 'CreatedByUserId')) {
                    DB::statement("ALTER TABLE tbladmin MODIFY COLUMN CreatedByUserId BIGINT UNSIGNED NULL DEFAULT NULL");
                }
            }
            if (Schema::hasTable('tbltest') && Schema::hasColumn('tbltest', 'CreatedByUserId')) {
                DB::statement("ALTER TABLE tbltest MODIFY COLUMN CreatedByUserId BIGINT UNSIGNED NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            \Log::warning('Migration column modification notice: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tblgroup')) {
            Schema::table('tblgroup', function (Blueprint $table) {
                if (Schema::hasColumn('tblgroup', 'EndDate')) {
                    $table->dropColumn('EndDate');
                }
                if (Schema::hasColumn('tblgroup', 'StartDate')) {
                    $table->dropColumn('StartDate');
                }
            });
        }
    }
};
