<?php

use App\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlaygroundController::class, 'index']);
