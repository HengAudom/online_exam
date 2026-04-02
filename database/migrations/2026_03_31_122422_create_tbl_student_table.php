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
        Schema::create('tblStudent', function (Blueprint $table) {
            $table->bigIncrements('StudentId');
            $table->unsignedBigInteger('UserId');
            $table->unsignedBigInteger('SkillId');
            $table->unsignedBigInteger('BatchId');
            $table->string('FirstName');
            $table->string('LastName');
            $table->string('Gender');
            $table->string('StudyShift');
            $table->string('Phone');
            $table->timestamps();

            $table->foreign('UserId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('SkillId')->references('SkillId')->on('tblSkill')->onDelete('cascade');
            $table->foreign('BatchId')->references('BatchId')->on('tblBatch')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblStudent');
    }
};
