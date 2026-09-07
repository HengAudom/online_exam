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
        if (!Schema::hasTable('tbladmin')) {
            Schema::create('tbladmin', function (Blueprint $table) {
                $table->bigIncrements('AdminId');
                $table->unsignedBigInteger('UserId')->nullable();
                $table->string('Username', 255)->unique();
                $table->string('Password', 255);
                $table->string('Role', 50)->default('Admin'); // SuperAdmin or Admin
                $table->string('Status', 50)->default('Active');
                $table->string('FirstName', 255)->nullable();
                $table->string('LastName', 255)->nullable();
                $table->string('Phone', 50)->nullable();
                $table->string('ProfileImage', 255)->nullable();
                $table->unsignedBigInteger('CreatedByUserId')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbladmin');
    }
};
