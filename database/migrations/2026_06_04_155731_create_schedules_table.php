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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('day_of_week', [
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'thursday'
            ]);

            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
