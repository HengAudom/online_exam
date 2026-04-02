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
        Schema::create('tblQuestion', function (Blueprint $table) {
            $table->bigIncrements('QuestionId');
            $table->unsignedBigInteger('TestId');
            $table->text('QuestionText');
            $table->integer('Points')->default(1);
            $table->timestamps();

            $table->foreign('TestId')->references('TestId')->on('tblTest')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblQuestion');
    }
};
