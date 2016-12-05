<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', ['uses' => 'HomeController@index', 'as' => 'dashboard']);

Route::get('/dashboardData', ['uses' => 'HomeController@dashboardData', 'as' => 'dashboardData']);

Route::post('dorecommendations', ['uses' => 'HomeController@doRecommendations', 'as' => 'dorecommendations']);

Route::get('history', ['uses' => 'HomeController@history', 'as' => 'history']);
Route::get('tasks', ['uses' => 'HomeController@tasks', 'as' => 'tasks']);
Route::get('provision', ['uses' => 'ProvisionController@create', 'as' => 'provision']);

Route::post('provision', ['uses' => 'ProvisionController@store', 'as' => 'doProvision']);

Route::get('map', ['uses' => 'MapController@index', 'as' => 'map']);
Route::post('map/dorecommendations', ['uses' => 'MapController@doRecommendations', 'as' => 'map/dorecommendations']);

Route::get('config', ['uses' => 'HomeController@config', 'as' => 'config']);

