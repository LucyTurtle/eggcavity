<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable(); // full URL to eggcave.com/static.eggcave.com
            $table->string('source_url')->nullable(); // link to page on eggcave.com
            $table->date('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable(); // extra scraped data (category, tags, etc.)
            $table->timestamps();
        });

        Schema::create('archive_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained()->cascadeOnDelete();
            $table->string('url'); // full URL
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_item_images');
        Schema::dropIfExists('archive_items');
    }
};
