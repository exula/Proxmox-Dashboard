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

Route::get('/', ['uses' => 'HomeController@index', 'as' => 'dashboard'])->middleware('proxmoxauth');

Route::get('/dash', ['uses' => 'HomeController@dash', 'as' => 'dash']);

Route::get('/dashboardData', ['uses' => 'HomeController@dashboardData', 'as' => 'dashboardData']);
Route::post('dorecommendations', ['uses' => 'HomeController@doRecommendations', 'as' => 'dorecommendations'])->middleware('proxmoxauth');
Route::get('history', ['uses' => 'HomeController@history', 'as' => 'history'])->middleware('proxmoxauth');
Route::get('tasks', ['uses' => 'HomeController@tasks', 'as' => 'tasks'])->middleware('proxmoxauth');
Route::get('provision', ['uses' => 'ProvisionController@create', 'as' => 'provision'])->middleware('proxmoxauth');
Route::post('provision', ['uses' => 'ProvisionController@store', 'as' => 'doProvision'])->middleware('proxmoxauth');
Route::get('map', ['uses' => 'MapController@index', 'as' => 'map'])->middleware('proxmoxauth');
Route::post('map/dorecommendations', ['uses' => 'MapController@doRecommendations', 'as' => 'map/dorecommendations'])->middleware('proxmoxauth');

Route::get('virtualmachines', ['uses' => 'HomeController@virtualmachines', 'as' => 'virtualmachines'])->middleware('proxmoxauth');

Route::get('config', ['uses' => 'HomeController@config', 'as' => 'config'])->middleware('proxmoxauth');

Route::post('/deploymentHook', function () {
    Log::info('Resetting opcache for '.php_sapi_name());
    opcache_reset();
});

Route::auth();
