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

Route::get('/', 'HomeController@index')->name('dashboard')->middleware('proxmoxauth');

Route::get('/dash', 'HomeController@dash')->name('dash');

Route::get('/dashboardData', 'HomeController@dashboardData')->name('dashboardData');
Route::post('dorecommendations', 'HomeController@doRecommendations')->name('dorecommendations')->middleware('proxmoxauth');
Route::get('history', 'HomeController@history')->name('history')->middleware('proxmoxauth');
Route::get('tasks', 'HomeController@tasks')->name('tasks')->middleware('proxmoxauth');
Route::get('provision', 'ProvisionController@create')->name('provision')->middleware('proxmoxauth');
Route::post('provision', 'ProvisionController@store')->name('doProvision')->middleware('proxmoxauth');
Route::get('map', 'MapController@index')->name('map')->middleware('proxmoxauth');
Route::post('map/dorecommendations', 'MapController@doRecommendations')->name('map/dorecommendations')->middleware('proxmoxauth');

Route::get('virtualmachines', 'HomeController@virtualmachines')->name('virtualmachines')->middleware('proxmoxauth');

Route::get('config', 'HomeController@config')->name('config')->middleware('proxmoxauth');

Route::post('/deploymentHook', function () {
    Log::info('Resetting opcache for '.php_sapi_name());
    opcache_reset();
});

Route::auth();
