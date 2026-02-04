<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix SQLite "foreign key mismatch" on wishlist tables.
     * Recreates tables without FK constraints so inserts work; referential integrity
     * is handled by the application. Only runs for SQLite.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('creature_wishlists');
        Schema::create('creature_wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('archive_item_id');
            $table->unsignedInteger('amount')->default(1);
            $table->string('gender')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'archive_item_id']);
        });

        Schema::dropIfExists('item_wishlists');
        Schema::create('item_wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('amount')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'item_id']);
        });

        Schema::dropIfExists('travel_wishlists');
        Schema::create('travel_wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('amount')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'item_id']);
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }
        Schema::dropIfExists('creature_wishlists');
        Schema::dropIfExists('item_wishlists');
        Schema::dropIfExists('travel_wishlists');
    }
};
