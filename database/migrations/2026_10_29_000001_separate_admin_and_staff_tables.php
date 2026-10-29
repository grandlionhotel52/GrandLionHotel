<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('employees')->whereNotIn('role', ['admin', 'staff'])->exists()) {
            throw new RuntimeException('Employees contains an unsupported account role.');
        }

        // Find every incoming constraint, including those in upgraded installations.
        $references = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $tableName) {
            if ($tableName === 'employees') {
                continue;
            }

            foreach (Schema::getForeignKeys($tableName) as $foreign) {
                if ($foreign['foreign_table'] !== 'employees') {
                    continue;
                }

                $column = $foreign['columns'][0];
                $role = match (true) {
                    str_contains($column, 'admin') => 'admin',
                    str_contains($column, 'staff'), $column === 'last_cleaned_by' => 'staff',
                    default => throw new RuntimeException("Cannot determine account role for {$tableName}.{$column}."),
                };

                if (count($foreign['columns']) !== 1 || $foreign['foreign_columns'] !== ['employee_id']) {
                    throw new RuntimeException("Unsupported employee reference on {$tableName}.{$column}.");
                }

                // Refuse to change the schema if an existing reference has the wrong role.
                if (DB::table($tableName)->whereNotNull($column)
                    ->whereNotIn($column, DB::table('employees')->select('employee_id')->where('role', $role))
                    ->exists()) {
                    throw new RuntimeException("Invalid {$role} reference on {$tableName}.{$column}.");
                }

                $references[] = [$tableName, $foreign, $role];
            }
        }

        if (DB::table('employees')->where('role', 'staff')->whereNotNull('created_by')
            ->whereNotIn('created_by', DB::table('employees')->select('employee_id')->where('role', 'admin'))
            ->exists()) {
            throw new RuntimeException('A staff account has an invalid creating administrator.');
        }

        foreach (['admins' => 'admin_id', 'staff' => 'staff_id'] as $tableName => $primaryKey) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName, $primaryKey): void {
                $table->bigIncrements($primaryKey);
                $table->string('name');
                $table->string('username', 50)->unique();
                $table->string('phone', 30)->nullable();
                $table->string('password');
                $table->timestamp('password_changed_at')->nullable();
                $table->rememberToken();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                if ($tableName === 'staff') {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by')->references('admin_id')->on('admins')->nullOnDelete();
                }
            });
        }

        // Copy raw values so password hashes, remember tokens and IDs stay unchanged.
        foreach (['admin' => 'admins', 'staff' => 'staff'] as $role => $tableName) {
            DB::table('employees')->where('role', $role)->orderBy('employee_id')
                ->chunkById(200, function ($accounts) use ($role, $tableName): void {
                    $rows = [];
                    foreach ($accounts as $account) {
                        $row = (array) $account;
                        $row[$role.'_id'] = $row['employee_id'];
                        unset($row['employee_id'], $row['role']);
                        if ($role === 'admin') {
                            unset($row['created_by']);
                        }
                        $rows[] = $row;
                    }
                    DB::table($tableName)->insert($rows);
                }, 'employee_id');
        }

        foreach ($references as [$tableName, $foreign, $role]) {
            Schema::table($tableName, function (Blueprint $table) use ($foreign, $role): void {
                $table->dropForeign($foreign['name'] ?? $foreign['columns']);
                $table->foreign($foreign['columns'], substr($table->getTable().'_'.$foreign['columns'][0].'_split_fk', 0, 63))
                    ->references($role.'_id')
                    ->on($role === 'admin' ? 'admins' : 'staff')
                    ->onDelete($foreign['on_delete'])
                    ->onUpdate($foreign['on_update']);
            });
        }

        Schema::drop('employees');
    }

    public function down(): void
    {
        // New accounts can have overlapping IDs after separation; merging would
        // require remapping both direct and polymorphic references again.
        throw new RuntimeException('Rollback is not supported after separating admin and staff accounts.');
    }
};
