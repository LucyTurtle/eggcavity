<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Give every user a username if they don't have one (so all wishlists are shareable)
        $users = DB::table('users')->whereNull('username')->get(['id', 'name']);

        foreach ($users as $user) {
            $base = Str::slug($user->name) ?: 'user';
            $username = $base;
            $n = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base . '-' . (++$n);
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        if (Schema::hasColumn('users', 'wishlist_share_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wishlist_share_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wishlist_share_enabled')->default(true)->after('role');
        });
    }
};
