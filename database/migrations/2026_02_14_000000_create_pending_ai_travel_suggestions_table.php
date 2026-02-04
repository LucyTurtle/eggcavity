<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_ai_travel_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->unsignedTinyInteger('sort_order')->default(0); // 1–5 per creature
            $table->timestamps();

            $table->unique(['archive_item_id', 'item_id']);
            $table->index('archive_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_ai_travel_suggestions');
    }
};
