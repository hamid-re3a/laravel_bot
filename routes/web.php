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
    return "dokan";
});


Route::group(['prefix' => 'bot/v1', 'namespace' => 'api\v1'], function () {
    Route::post('dokan', 'TelegramController@dokan');
    Route::get('info', 'TelegramController@info');
});
