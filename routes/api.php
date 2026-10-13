<?php

use App\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [PlaygroundController::class, 'health']);
Route::post('/predict', [PlaygroundController::class, 'predict']);
Route::post('/decide', [PlaygroundController::class, 'decide']);
