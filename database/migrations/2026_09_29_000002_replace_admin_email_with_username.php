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
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->after('name');
        });

        $usedUsernames = DB::table('staff')
            ->pluck('username')
            ->mapWithKeys(static fn (string $username): array => [strtolower($username) => true])
            ->all();

        DB::table('admins')
            ->orderBy('admin_id')
            ->get(['admin_id', 'name', 'email'])
            ->each(function (object $admin) use (&$usedUsernames): void {
                $emailPrefix = Str::before(strtolower(trim((string) $admin->email)), '@');
                $nameSlug = Str::slug(strtolower(trim((string) $admin->name)), '.');
                $base = preg_replace('/[^a-z0-9._-]/', '', $emailPrefix ?: $nameSlug) ?: 'admin';
                $base = substr($base, 0, 42);
                $candidate = $base;
                $suffix = 1;

                while (isset($usedUsernames[$candidate])) {
                    $candidate = substr($base, 0, 42).'-'.$suffix++;
                }

                $usedUsernames[$candidate] = true;

                DB::table('admins')
                    ->where('admin_id', $admin->admin_id)
                    ->update(['username' => $candidate]);
            });

        Schema::table('admins', function (Blueprint $table): void {
            $table->string('username', 50)->nullable(false)->change();
            $table->unique('username');
            $table->dropUnique('admins_email_unique');
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('name');
        });

        DB::table('admins')
            ->orderBy('admin_id')
            ->get(['admin_id', 'username'])
            ->each(function (object $admin): void {
                DB::table('admins')
                    ->where('admin_id', $admin->admin_id)
                    ->update(['email' => $admin->username.'@admin.invalid']);
            });

        Schema::table('admins', function (Blueprint $table): void {
            $table->unique('email');
            $table->dropUnique('admins_username_unique');
            $table->dropColumn('username');
        });
    }
};
