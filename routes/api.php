<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public route to view available time slots
Route::get('/timeslots/available', [TimeSlotController::class, 'available']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Time slot routes (consultant only for create, update, delete)
    Route::apiResource('/timeslots', TimeSlotController::class);
    
    // Reservation specific routes (must be before resource routes)
    Route::get('/reservations/future', [ReservationController::class, 'future']);
    Route::get('/consultant/reservations', [ReservationController::class, 'consultantReservations']);
    
    // Reservation resource routes
    Route::apiResource('/reservations', ReservationController::class)->except(['update']);
});

// Fallback for undefined API routes
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found'
    ], 404);
}); 