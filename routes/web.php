<?php

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
Route::get('/checkBind/{openid}','EarlySign@checkBind');
Route::get('/checkSign/{openid}','EarlySign@checkSign');
Route::any('/bind','EarlySign@Bind');
Route::any('/sign/{openid}','EarlySign@sign');
Route::get('queryById/{flag}/{openid}','EarlySign@queryById');
Route::any('/queryMany/{flag}/{num}','EarlySign@querymany');