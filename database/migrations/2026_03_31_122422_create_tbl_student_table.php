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
            $table->string('StudentCode', 50)->nullable()->unique();
            $table->unsignedBigInteger('UserId')->nullable();
            $table->unsignedBigInteger('SkillId')->nullable();
            $table->unsignedBigInteger('GroupId')->nullable();
            $table->string('FirstName');
            $table->string('LastName');
            $table->string('Gender')->default('Male');
            $table->string('StudyShift')->default('Morning');
            $table->string('Phone')->nullable();
            $table->string('EnrolledMonth', 50)->nullable();
            $table->string('EnrolledYear', 10)->nullable();
            $table->string('DurationMonths', 50)->nullable();
            $table->longText('Photo')->nullable();
            $table->timestamps();

            $table->foreign('SkillId')->references('SkillId')->on('tblSkill')->onDelete('set null');
            $table->foreign('GroupId')->references('GroupId')->on('tblgroup')->onDelete('set null');
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
