<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProvisionController;
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

Route::get('/', [HomeController::class, 'index'])->name('dashboard')->middleware('proxmoxauth');

Route::get('/dash', [HomeController::class, 'dash'])->name('dash');

Route::get('/dashboardData', [HomeController::class, 'dashboardData'])->name('dashboardData');
Route::post('dorecommendations', [HomeController::class, 'doRecommendations'])->name('dorecommendations')->middleware('proxmoxauth');
Route::get('history', [HomeController::class, 'history'])->name('history')->middleware('proxmoxauth');
Route::get('tasks', [HomeController::class, 'tasks'])->name('tasks')->middleware('proxmoxauth');
Route::get('provision', [ProvisionController::class, 'create'])->name('provision')->middleware('proxmoxauth');
Route::post('provision', [ProvisionController::class, 'store'])->name('doProvision')->middleware('proxmoxauth');
Route::get('map', [MapController::class, 'index'])->name('map')->middleware('proxmoxauth');
Route::post('map/dorecommendations', [MapController::class, 'doRecommendations'])->name('map/dorecommendations')->middleware('proxmoxauth');

Route::get('virtualmachines', [HomeController::class, 'virtualmachines'])->name('virtualmachines')->middleware('proxmoxauth');

Route::get('config', [HomeController::class, 'config'])->name('config')->middleware('proxmoxauth');

Route::post('/deploymentHook', function () {
    Log::info('Resetting opcache for '.php_sapi_name());
    opcache_reset();
});

Route::auth();
