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
        if (!Schema::hasTable('tblgroup') && !Schema::hasTable('tblGroup')) {
            Schema::create('tblgroup', function (Blueprint $table) {
                $table->bigIncrements('GroupId');
                $table->string('GroupName');
                $table->date('StartDate')->nullable();
                $table->date('EndDate')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblgroup');
    }
};
