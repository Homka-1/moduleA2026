<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('events', [EventController::class, 'listEvents']);
        Route::post('events', [EventController::class, 'createEvent']);
        Route::get('events/{event}', [EventController::class, 'getEvent'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::put('events/{event}', [EventController::class, 'updateEvent'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::delete('/events/{event}', [EventController::class, 'deleteEvent'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::get('/events/{event}/my-status/', [EventController::class, 'myStatus'])->missing(fn() => response()->json(['message' => 'Not found'], 404));

        Route::get('my-participant', [ParticipantController::class, 'myParticipant']);
        Route::post('participants', [ParticipantController::class, 'createParticipant']);
        Route::put('participants/{participant}', [ParticipantController::class, 'updateParticipant'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::delete('participants/{participant}', [ParticipantController::class, 'deleteParticipant'])->missing(fn() => response()->json(['message' => 'Not found'], 404));


        Route::post('/registrations', [RegistrationController::class, 'createRegistration']);
        Route::patch('/registrations/{registration}/confirm', [RegistrationController::class, 'confirmRegistration'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::patch('/registrations/{registration}/cancel', [RegistrationController::class, 'cancelRegistration'])->missing(fn() => response()->json(['message' => 'Not found'], 404));
        Route::get('/my-registrations', [RegistrationController::class, 'myRegistrations']);
    });
});
