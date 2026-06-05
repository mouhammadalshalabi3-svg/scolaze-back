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
        Schema::create('absence_justifications', function (Blueprint $table) {
            $table->id();
            $table->text('reason');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ]);

            
            $table->foreignId('attendance_id')->unique()->constrained('attendance')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents') ->constrained('parents')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_justifications');
    }
};
