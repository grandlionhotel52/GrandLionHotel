<?php

use App\Models\Room;
use App\Http\Controllers\PayMongoWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/rooms', function () {
    return Room::query()->availableForBooking()->latest()->paginate(10);
});

Route::post('/webhooks/paymongo', PayMongoWebhookController::class)->name('webhooks.paymongo');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
