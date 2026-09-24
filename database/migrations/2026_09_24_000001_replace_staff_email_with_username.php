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
        Schema::table('staff', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->after('name');
        });

        $usedUsernames = [];

        DB::table('staff')
            ->orderBy('staff_id')
            ->get(['staff_id', 'name', 'email'])
            ->each(function (object $staff) use (&$usedUsernames): void {
                $emailPrefix = Str::before(strtolower(trim((string) $staff->email)), '@');
                $nameSlug = Str::slug(strtolower(trim((string) $staff->name)), '.');
                $base = preg_replace('/[^a-z0-9._-]/', '', $emailPrefix ?: $nameSlug) ?: 'staff';
                $base = substr($base, 0, 42);
                $candidate = $base;
                $suffix = 1;

                while (isset($usedUsernames[$candidate])) {
                    $candidate = substr($base, 0, 42).'-'.$suffix++;
                }

                $usedUsernames[$candidate] = true;

                DB::table('staff')
                    ->where('staff_id', $staff->staff_id)
                    ->update(['username' => $candidate]);
            });

        Schema::table('staff', function (Blueprint $table): void {
            $table->unique('username');
            $table->dropUnique('staff_email_unique');
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('name');
        });

        DB::table('staff')
            ->orderBy('staff_id')
            ->get(['staff_id', 'username'])
            ->each(function (object $staff): void {
                DB::table('staff')
                    ->where('staff_id', $staff->staff_id)
                    ->update(['email' => $staff->username.'@staff.invalid']);
            });

        Schema::table('staff', function (Blueprint $table): void {
            $table->unique('email');
            $table->dropUnique('staff_username_unique');
            $table->dropColumn('username');
        });
    }
};
