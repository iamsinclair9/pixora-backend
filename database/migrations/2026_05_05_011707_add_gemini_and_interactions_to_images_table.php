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
        Schema::table('images', function (Blueprint $column) {
            $column->string('ai_category')->nullable()->after('location');
            $column->text('ai_description')->nullable()->after('ai_category');
            $column->integer('likes_count')->default(0)->after('ai_description');
            $column->integer('dislikes_count')->default(0)->after('likes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $column) {
            $column->dropColumn(['ai_category', 'ai_description', 'likes_count', 'dislikes_count']);
        });
    }
};
