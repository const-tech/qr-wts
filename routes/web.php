<?php

use Almarwa\WhatsappGateway\Http\Controllers\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/',                  [SubscribeController::class, 'landing'])->name('landing');
Route::get('/register',          [SubscribeController::class, 'showRegister'])->name('register.show');
Route::post('/register',         [SubscribeController::class, 'register'])->name('register');
Route::get('/connect/{token}',   [SubscribeController::class, 'connect'])->name('connect');
Route::get('/poll/{token}',      [SubscribeController::class, 'poll'])->name('poll');
Route::post('/restart/{token}',  [SubscribeController::class, 'restart'])->name('restart');
Route::get('/expired/{token}',   [SubscribeController::class, 'expired'])->name('expired');
