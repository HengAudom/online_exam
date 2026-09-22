<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblauditlog')) {
            Schema::create('tblauditlog', function (Blueprint $table) {
                $table->id('AuditLogId');
                $table->unsignedBigInteger('UserId')->nullable()->index();
                $table->string('UserName', 191)->nullable();
                $table->string('UserRole', 50)->default('User');
                $table->string('Action', 100)->index();
                $table->string('Module', 50)->index();
                $table->string('Target', 191)->nullable()->index();
                $table->string('Status', 30)->default('Success');
                $table->text('Details')->nullable();
                $table->string('IpAddress', 45)->nullable();
                $table->text('UserAgent')->nullable();
                $table->timestamp('CreatedAt')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblauditlog');
    }
};
