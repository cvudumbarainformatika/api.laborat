<?php

use App\Http\Controllers\Api\v1\ScrapperController;
use App\Http\Controllers\AutogenController;
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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/autogen', [AutogenController::class, 'index']);
<<<<<<< HEAD
Route::get('/autogen/coba', [AutogenController::class, 'getDetOrderList']);
=======
Route::get('/autogen/coba', [AutogenController::class, 'coba']);

Route::get('/scrapper/coba', [ScrapperController::class, 'index']);
>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01
