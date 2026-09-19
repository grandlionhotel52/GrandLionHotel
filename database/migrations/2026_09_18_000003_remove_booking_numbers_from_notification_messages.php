<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->select(['id', 'data'])
            ->orderBy('id')
            ->chunkById(200, function ($notifications): void {
                foreach ($notifications as $notification) {
                    $data = json_decode((string) $notification->data, true);
                    if (!is_array($data) || !is_string($data['message'] ?? null)) {
                        continue;
                    }

                    $message = preg_replace('/^Booking\s*#\s*\d+\b/i', 'Your booking', $data['message']);
                    $message = preg_replace('/\bbooking\s*#\s*\d+\b/i', 'your booking', (string) $message);

                    if ($message === $data['message']) {
                        continue;
                    }

                    $data['message'] = $message;

                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update(['data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        // Removed internal booking numbers cannot be reconstructed safely.
    }
};
