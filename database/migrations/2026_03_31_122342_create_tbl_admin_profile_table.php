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
        Schema::create('tblAdminProfile', function (Blueprint $table) {
            $table->bigIncrements('AdminProfileId');
            $table->unsignedBigInteger('UserId');
            $table->unsignedBigInteger('CreatedByUserId');
            $table->string('FirstName');
            $table->string('LastName');
            $table->string('Phone');
            $table->timestamps();

            $table->foreign('UserId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('CreatedByUserId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblAdminProfile');
    }
};
