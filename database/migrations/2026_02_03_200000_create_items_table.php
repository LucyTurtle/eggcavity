<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->string('rarity')->nullable(); // e.g. "r83 (uncommon)"
            $table->string('use')->nullable(); // e.g. "Food Item"
            $table->string('associated_shop')->nullable(); // e.g. "Finley's Flavors"
            $table->string('restock_price')->nullable(); // e.g. "1,502 EC"
            $table->boolean('is_retired')->default(false);
            $table->date('first_appeared')->nullable(); // extracted from "around January 7, 2026"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
