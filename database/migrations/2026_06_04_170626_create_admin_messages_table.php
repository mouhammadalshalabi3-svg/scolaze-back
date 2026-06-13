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
    Schema::create('admin_messages', function (Blueprint $table) {
    $table->id();

    $table->string('title');

    $table->text('message');

    $table->boolean('is_read')->default(false);

    $table->timestamp('sent_at')->useCurrent();

    $table->foreignId('admin_id')
        ->constrained('admins')
        ->cascadeOnDelete();

    $table->foreignId('parent_id')
        ->nullable()
        ->constrained('parents')
        ->cascadeOnDelete();

    $table->foreignId('teacher_id')
        ->nullable()
        ->constrained('teachers')
        ->cascadeOnDelete();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
    }
};
