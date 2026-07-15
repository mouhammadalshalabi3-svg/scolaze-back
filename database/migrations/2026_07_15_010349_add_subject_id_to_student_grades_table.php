<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('student_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
            $table->foreignId('exam_id')->nullable(false)->change();
        });
    }
};