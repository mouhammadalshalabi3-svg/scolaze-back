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
      Schema::create('periods', function (Blueprint $table) {
    $table->id();
    $table->integer('period_number');
    $table->time('start_time');
    $table->time('end_time');
    $table->enum('day_of_week', [
        'sunday','monday','tuesday','wednesday','thursday'
    ]);
    $table->enum('status', [
        'completed','absent','cancelled'
    ])->nullable();
    $table->foreignId('assignment_id')
        ->constrained('teaching_assignments')
        ->cascadeOnDelete();
    $table->timestamps();
});
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
