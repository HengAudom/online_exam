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
        Schema::create('tblStudentSubmission', function (Blueprint $table) {
            $table->bigIncrements('SubmissionId');
            $table->unsignedBigInteger('StudentId');
            $table->unsignedBigInteger('TestId');
            $table->timestamp('StartedAt')->nullable();
            $table->timestamp('CompletedAt')->nullable();
            $table->integer('TotalCorrect')->default(0);
            $table->decimal('Score', 8, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('StudentId')->references('StudentId')->on('tblStudent')->onDelete('cascade');
            $table->foreign('TestId')->references('TestId')->on('tblTest')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblStudentSubmission');
    }
};
