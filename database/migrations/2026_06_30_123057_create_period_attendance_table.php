<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('status', ['completed', 'absent', 'cancelled']);
            $table->foreignId('supervisor_id')->constrained();
            $table->timestamps();

            $table->unique(['period_id', 'session_date']); // ما يصير تسجيل مكرر لنفس الحصة بنفس اليوم
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_attendance');
    }
};