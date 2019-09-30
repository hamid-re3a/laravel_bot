<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix'=>'v1','namespace' => 'api\v1'], function () {
    // Public routes

    Route::resource('user', 'UserController');
    Route::resource('service_provider', 'ServiceProviderController',['only']);
    Route::get('login','AuthController@login');




    Route::group(['middleware'=>'auth:api'],function (){
        //Login required routes

        Route::group(['middleware'=>'onlyActiveUser'],function () {
            // These routes can be accessed only by actived users

        });
    });
});
