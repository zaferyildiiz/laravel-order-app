<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Data\DataController;
use App\Http\Controllers\Order\OrderController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('data-import',[DataController::class,'index']);


Route::prefix('order')->group(function () {
    Route::get('/list_orders',[OrderController::class,'index']);
    Route::post('/create_order',[OrderController::class,'store']);
    Route::delete('/delete_order/{id}',[OrderController::class,'delete']);
});
