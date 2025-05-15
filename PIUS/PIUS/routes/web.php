<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlowfishController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/v1/blowfish',
 [BlowfishController::class, 'validApi']);