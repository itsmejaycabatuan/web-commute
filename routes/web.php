<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/home', function () {
    return view('home');
})->name('home');


Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/login',function (){
    return view('login');
})->name('login');

Route::get('/dashboard/commuter', function () {
    return view('commuter.dashboard');
})->name('commuter.dashboard');

Route::get('/commuter/commuter', function () {
    return view('commuter.commuter');
})->name('commuter.commuter');
