<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('image_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'image_id']); // one bookmark per user per image
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
