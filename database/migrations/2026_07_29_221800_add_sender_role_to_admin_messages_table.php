<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_messages', function (Blueprint $table) {
            $table->enum('sender_role', ['admin', 'teacher'])->nullable()->after('teacher_id');
        });

        // نعبّي القيمة للسجلات القديمة: إذا في admin_id يبقى المرسل أدمن، غير هيك أستاذ
        DB::table('admin_messages')->whereNotNull('admin_id')->update(['sender_role' => 'admin']);
        DB::table('admin_messages')->whereNull('admin_id')->update(['sender_role' => 'teacher']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_messages', function (Blueprint $table) {
            $table->dropColumn('sender_role');
        });
    }
};
