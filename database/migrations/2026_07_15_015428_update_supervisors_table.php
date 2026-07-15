<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->decimal('monthly_salary', 8, 2)->nullable()->after('email');
            $table->foreignId('grade_id')->nullable()->after('monthly_salary')->constrained('grades')->nullOnDelete();
            $table->string('photo')->nullable()->after('grade_id');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn(['monthly_salary', 'grade_id', 'photo']);
            $table->string('email')->nullable(false)->change();
        });
    }
};