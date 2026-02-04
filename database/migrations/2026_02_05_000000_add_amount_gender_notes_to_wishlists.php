<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creature_wishlists', function (Blueprint $table) {
            $table->unsignedInteger('amount')->default(1)->after('archive_item_id');
            $table->string('gender')->nullable()->after('amount'); // male, female, non-binary, no_preference
            $table->text('notes')->nullable()->after('gender');
        });

        Schema::table('item_wishlists', function (Blueprint $table) {
            $table->unsignedInteger('amount')->default(1)->after('item_id');
            $table->text('notes')->nullable()->after('amount');
        });

        Schema::table('travel_wishlists', function (Blueprint $table) {
            $table->unsignedInteger('amount')->default(1)->after('item_id');
            $table->text('notes')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('creature_wishlists', function (Blueprint $table) {
            $table->dropColumn(['amount', 'gender', 'notes']);
        });
        Schema::table('item_wishlists', function (Blueprint $table) {
            $table->dropColumn(['amount', 'notes']);
        });
        Schema::table('travel_wishlists', function (Blueprint $table) {
            $table->dropColumn(['amount', 'notes']);
        });
    }
};
