<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['tblstudent', 'tblStudent'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (!Schema::hasColumn($tbl, 'TelegramChatId')) {
                        $table->string('TelegramChatId', 64)->nullable()->after('Phone');
                    }
                    if (!Schema::hasColumn($tbl, 'TelegramUsername')) {
                        $table->string('TelegramUsername', 128)->nullable()->after('TelegramChatId');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['tblstudent', 'tblStudent'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (Schema::hasColumn($tbl, 'TelegramUsername')) {
                        $table->dropColumn('TelegramUsername');
                    }
                    if (Schema::hasColumn($tbl, 'TelegramChatId')) {
                        $table->dropColumn('TelegramChatId');
                    }
                });
            }
        }
    }
};
