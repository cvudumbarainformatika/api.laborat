<?php

use App\Events\PlaygroundEvent;
use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Api\v1\ScrapperController;
use App\Http\Controllers\AutogenController;
use App\Http\Controllers\DvlpController;
use App\Http\Controllers\PrintController;
use Illuminate\Http\Request;
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
Route::get('/autogen/coba', [AutogenController::class, 'coba']);
Route::get('/autogen/gennoreg', [AutogenController::class, 'gennoreg']);
Route::get('/autogen/coba-api', [AutogenController::class, 'coba_api']);
Route::get('/autogen/wawan', [AutogenController::class, 'wawan']);
Route::get('/autogen/wawanpost', [AutogenController::class, 'wawanpost']);
Route::get('/autogen/set-min-max', [AutogenController::class, 'setMinMax']);
Route::get('/autogen/synct', [JadwalController::class, 'sycncroneJadwal']);

Route::get('/dvlp', [DvlpController::class, 'index']);
Route::get('/dvlp/antrian', [DvlpController::class, 'antrian']);



Route::get('/print/page', [PrintController::class, 'index']);

Route::get('/unsubscribe/{user}', function (Request $request, $user) {
    if (!$request->hasValidSignature()) {
        abort(401);
    }

    return $user;
})->name('unsubscribe')->middleware('signed');

// Route::get('/playground', function (Request $request) {
//    event(New PlaygroundEvent());

//    return null;
// });
