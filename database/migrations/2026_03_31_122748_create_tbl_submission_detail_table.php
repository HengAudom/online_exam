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
        Schema::create('tblSubmissionDetail', function (Blueprint $table) {
            $table->bigIncrements('DetailId');
            $table->unsignedBigInteger('SubmissionId');
            $table->unsignedBigInteger('QuestionId');
            $table->unsignedBigInteger('SelectedAnswerId')->nullable();
            $table->boolean('IsCorrect');
            $table->timestamps();

            $table->foreign('SubmissionId')->references('SubmissionId')->on('tblStudentSubmission')->onDelete('cascade');
            $table->foreign('QuestionId')->references('QuestionId')->on('tblQuestion')->onDelete('cascade');
            $table->foreign('SelectedAnswerId')->references('AnswerId')->on('tblAnswer')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblSubmissionDetail');
    }
};
