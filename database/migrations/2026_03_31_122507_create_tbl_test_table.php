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
            $table->unsignedBigInteger('BatchId')->nullable();
            $table->unsignedBigInteger('CreatedByUserId');
            $table->string('TestName');
            $table->integer('DurationMinutes');
            $table->integer('TotalMarks');
            $table->timestamp('ScheduledAt')->nullable();
            $table->timestamp('FinishedAt')->nullable();
            $table->enum('Status', ['Draft', 'Published'])->default('Draft');
            $table->timestamps();

            $table->foreign('SkillId')->references('SkillId')->on('tblSkill')->onDelete('cascade');
            $table->foreign('BatchId')->references('BatchId')->on('tblBatch')->onDelete('set null');
            $table->foreign('CreatedByUserId')->references('id')->on('users')->onDelete('cascade');
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
