<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->name('api.')
    ->group(function () {
        Route::apiResource('clinics', ClinicController::class);
        Route::apiResource('units', UnitController::class);
        Route::apiResource('services', ServiceController::class);
        Route::apiResource('appointments', AppointmentController::class);

        Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
            ->name('appointments.cancel');
        Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
            ->name('appointments.reschedule');
    });
