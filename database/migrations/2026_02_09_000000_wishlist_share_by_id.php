<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upSqlite();
        } else {
            $this->upMysql();
        }
    }

    private function upSqlite(): void
    {
        $hasSlug = Schema::hasColumn('users', 'wishlist_share_slug');
        $hasToken = Schema::hasColumn('users', 'wishlist_share_token');
        $hasEnabled = Schema::hasColumn('users', 'wishlist_share_enabled');

        if ($hasEnabled && !$hasSlug && !$hasToken) {
            return;
        }

        if (!$hasSlug && !$hasToken) {
            // No slug/token columns; just add the new column
            DB::statement('ALTER TABLE users ADD COLUMN wishlist_share_enabled TINYINT(1) NOT NULL DEFAULT 0');
            return;
        }

        DB::statement('CREATE TABLE users_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            email_verified_at DATETIME NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT \'user\',
            wishlist_share_enabled TINYINT(1) NOT NULL DEFAULT 0,
            remember_token VARCHAR(100) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        $enabledExpr = $hasSlug && $hasToken
            ? "CASE WHEN wishlist_share_slug IS NOT NULL OR wishlist_share_token IS NOT NULL THEN 1 ELSE 0 END"
            : ($hasSlug
                ? 'CASE WHEN wishlist_share_slug IS NOT NULL THEN 1 ELSE 0 END'
                : 'CASE WHEN wishlist_share_token IS NOT NULL THEN 1 ELSE 0 END');

        DB::statement("INSERT INTO users_new (id, name, email, email_verified_at, password, role, wishlist_share_enabled, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, role, {$enabledExpr}, remember_token, created_at, updated_at FROM users");

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users(email)');
    }

    private function upMysql(): void
    {
        if (!Schema::hasColumn('users', 'wishlist_share_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('wishlist_share_enabled')->default(false)->after('role');
            });
        }

        if (Schema::hasColumn('users', 'wishlist_share_slug')) {
            DB::table('users')->whereNotNull('wishlist_share_slug')->update(['wishlist_share_enabled' => true]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wishlist_share_slug');
            });
        }
        if (Schema::hasColumn('users', 'wishlist_share_token')) {
            DB::table('users')->whereNotNull('wishlist_share_token')->update(['wishlist_share_enabled' => true]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wishlist_share_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return; // irreversible without storing previous slug/token
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wishlist_share_enabled');
            $table->string('wishlist_share_slug', 100)->nullable()->unique()->after('role');
        });
    }
};
