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
        if (Schema::hasColumn('users', 'username')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->unique()->after('role');
        });

        // Give existing users with sharing enabled a username from their name
        $users = DB::table('users')
            ->where('wishlist_share_enabled', true)
            ->whereNull('username')
            ->get(['id', 'name']);

        foreach ($users as $user) {
            $base = Str::slug($user->name) ?: 'user';
            $username = $base;
            $n = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base . '-' . (++$n);
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
