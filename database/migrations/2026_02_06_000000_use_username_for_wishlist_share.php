<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER TABLE DROP COLUMN easily, so we need to recreate the table
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            if (Schema::hasColumn('users', 'wishlist_share_token') && !Schema::hasColumn('users', 'wishlist_share_slug')) {
                DB::statement('DROP INDEX IF EXISTS users_wishlist_share_token_unique');
                DB::statement('CREATE TABLE users_new AS SELECT id, name, email, email_verified_at, password, role, NULL as wishlist_share_slug, remember_token, created_at, updated_at FROM users');
                DB::statement('DROP TABLE users');
                DB::statement('ALTER TABLE users_new RENAME TO users');
                DB::statement('CREATE UNIQUE INDEX users_email_unique ON users(email)');
                DB::statement('CREATE UNIQUE INDEX users_wishlist_share_slug_unique ON users(wishlist_share_slug)');
            }
        } else {
            // For MySQL/PostgreSQL
            if (Schema::hasColumn('users', 'wishlist_share_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('wishlist_share_token');
                });
            }
            if (!Schema::hasColumn('users', 'wishlist_share_slug')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('wishlist_share_slug', 100)->nullable()->unique()->after('role');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wishlist_share_slug');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('wishlist_share_token', 64)->nullable()->unique()->after('role');
        });
    }
};
