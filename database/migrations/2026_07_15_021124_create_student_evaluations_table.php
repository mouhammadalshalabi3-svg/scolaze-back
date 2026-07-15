<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('grade'); // من 10
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};