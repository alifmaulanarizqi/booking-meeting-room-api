<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\RoomController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/rooms', [RoomController::class, 'store'])->middleware('role.admin');
        Route::get('/rooms/available', [RoomController::class, 'available']);

        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    });
});