<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

// The API has no web UI (the SPA lives on the portal subdomain), so the root is a JSON status.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'status' => 'ok',
]));

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
