<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_images', function (Blueprint $table): void {
            $table->bigIncrements('room_image_id');
            $table->unsignedBigInteger('room_id');
            $table->string('path');
            $table->string('caption', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('room_id')
                ->references('room_id')
                ->on('rooms')
                ->cascadeOnDelete();
            $table->index(['room_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_images');
    }
};
