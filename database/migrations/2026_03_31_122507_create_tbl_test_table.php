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
        Schema::create('tblTest', function (Blueprint $table) {
            $table->bigIncrements('TestId');
            $table->unsignedBigInteger('SkillId');
            $table->unsignedBigInteger('GroupId')->nullable();
            $table->unsignedBigInteger('CreatedByUserId')->nullable();
            $table->string('TestName');
            $table->integer('DurationMinutes');
            $table->integer('TotalMarks');
            $table->timestamp('ScheduledAt')->nullable();
            $table->timestamp('FinishedAt')->nullable();
            $table->enum('Status', ['Draft', 'Published'])->default('Draft');
            $table->timestamps();

            $table->foreign('SkillId')->references('SkillId')->on('tblSkill')->onDelete('cascade');
            $table->foreign('GroupId')->references('GroupId')->on('tblgroup')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblTest');
    }
};
