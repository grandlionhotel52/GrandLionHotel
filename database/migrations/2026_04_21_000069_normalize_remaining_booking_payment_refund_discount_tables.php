<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_date_discounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('room_date_discounts', 'discount_date_start')) {
                $table->date('discount_date_start')->nullable()->after('room_id');
            }

            if (!Schema::hasColumn('room_date_discounts', 'discount_date_end')) {
                $table->date('discount_date_end')->nullable()->after('discount_date_start');
            }
        });

        if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
            DB::table('room_date_discounts')->whereNull('discount_date_start')->update([
                'discount_date_start' => DB::raw('discount_date'),
                'discount_date_end' => DB::raw('discount_date'),
            ]);
        }

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->index('room_id', 'room_date_discounts_room_id_index');

            if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $this->dropIndexIfExists($table, 'room_date_discounts_room_date_unique', 'unique');
                $this->dropIndexIfExists($table, 'room_date_discounts_discount_date_index');
            }

            if (Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $this->dropForeignIfExists($table, 'room_date_discounts_admin_id_foreign');
            }
        });

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $table->dropColumn('discount_date');
            }

            if (Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $table->dropColumn('admin_id');
            }
        });

        DB::table('room_date_discounts')
            ->whereNull('discount_date_start')
            ->update(['discount_date_start' => DB::raw('discount_date_end')]);

        DB::table('room_date_discounts')
            ->whereNull('discount_date_end')
            ->update(['discount_date_end' => DB::raw('discount_date_start')]);

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->date('discount_date_start')->nullable(false)->change();
            $table->date('discount_date_end')->nullable(false)->change();
            $table->unique(['room_id', 'discount_date_start', 'discount_date_end'], 'room_date_discounts_room_range_unique');
            $table->index(['discount_date_start', 'discount_date_end'], 'room_date_discounts_range_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $this->dropForeignIfExists($table, 'payments_staff_id_foreign');
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $this->dropForeignIfExists($table, 'booking_guest_details_staff_id_foreign');
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_guest_details', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (!Schema::hasColumn('booking_guest_details', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('payment_preference')
                    ->constrained('staff', 'staff_id')
                    ->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('verified_at')
                    ->constrained('staff', 'staff_id')
                    ->nullOnDelete();
            }
        });

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $this->dropIndexIfExists($table, 'room_date_discounts_room_range_unique', 'unique');
            $this->dropIndexIfExists($table, 'room_date_discounts_range_index');

            if (!Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $table->date('discount_date')->nullable()->after('room_id');
            }

            if (!Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('discount_percent')
                    ->constrained('admins', 'admin_id')
                    ->nullOnDelete();
            }
        });

        DB::table('room_date_discounts')
            ->whereNull('discount_date')
            ->update(['discount_date' => DB::raw('discount_date_start')]);

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->date('discount_date')->nullable(false)->change();
            $table->dropColumn(['discount_date_start', 'discount_date_end']);
            $table->unique(['room_id', 'discount_date'], 'room_date_discounts_room_date_unique');
            $table->index('discount_date');
        });
    }

    private function dropForeignIfExists(Blueprint $table, string $constraintName): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            $column = match ($constraintName) {
                'room_date_discounts_admin_id_foreign' => 'admin_id',
                'payments_staff_id_foreign' => 'staff_id',
                'booking_guest_details_staff_id_foreign' => 'staff_id',
                default => null,
            };

            if ($column !== null) {
                $table->dropForeign([$column]);
            }

            return;
        }

        try {
            $table->dropForeign($constraintName);
        } catch (Throwable) {
            //
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $indexName, string $type = 'index'): void
    {
        try {
            if ($type === 'unique') {
                $table->dropUnique($indexName);
                return;
            }

            $table->dropIndex($indexName);
        } catch (Throwable) {
            //
        }
    }
};
