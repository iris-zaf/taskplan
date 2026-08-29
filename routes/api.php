<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/guests/ticket/{code}', [GuestController::class, 'ticket']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('events', EventController::class);

    Route::get('/events/{event}/guests', [GuestController::class, 'index']);
    Route::post('/events/{event}/guests', [GuestController::class, 'store']);
    Route::delete('/events/{event}/guests/{guest}', [GuestController::class, 'destroy']);
    Route::post('/guests/checkin', [GuestController::class, 'checkin']);
});
