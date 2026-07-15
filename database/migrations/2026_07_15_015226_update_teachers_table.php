<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->decimal('rate_per_lesson', 8, 2)->nullable()->after('salary_type');
            $table->foreignId('subject_id')->nullable()->after('rate_per_lesson')->constrained('subjects')->nullOnDelete();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['rate_per_lesson', 'subject_id']);
            $table->string('email')->nullable(false)->change();
        });
    }
};