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
        Schema::table('posts', function (Blueprint $table) {
            // 在導覽列顯示的文字；留空則使用 title
            $table->string('nav_label')->nullable()->after('slug');
            // 在導覽列的排序；NULL 代表不顯示在導覽列
            $table->integer('nav_order')->nullable()->after('nav_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['nav_label', 'nav_order']);
        });
    }
};
