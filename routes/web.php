<?php

use App\Events\PlaygroundEvent;
use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StokOpnameController;
use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Api\v1\ScrapperController;
use App\Http\Controllers\AutogenController;
use App\Http\Controllers\DvlpController;
use App\Http\Controllers\PrintController;
use App\Websockets\SocketHandler\UpdatePostSocketHandler;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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


WebSocketsRouter::webSocket('/socket/update-post', UpdatePostSocketHandler::class);

Route::get('/', function () {
    return view('welcome');
});
// stok opname
// Route::get('/opname', [StokOpnameController::class, 'storeMonthly']);

Route::get('/autogen', [AutogenController::class, 'index']);
Route::get('/autogen/coba', [AutogenController::class, 'coba']);
Route::get('/autogen/gennoreg', [AutogenController::class, 'gennoreg']);
Route::get('/autogen/coba-api', [AutogenController::class, 'coba_api']);
Route::get('/autogen/wawan', [AutogenController::class, 'wawan']);
Route::get('/autogen/wawanpost', [AutogenController::class, 'wawanpost']);
Route::get('/autogen/set-min-max', [AutogenController::class, 'setMinMax']);
Route::get('/autogen/synct', [JadwalController::class, 'sycncroneJadwal']);
Route::get('/autogen/http-res-bpjs', [AutogenController::class, 'httpRespBpjs']);

Route::get('/dvlp', [DvlpController::class, 'index']);
Route::get('/dvlp/antrian', [DvlpController::class, 'antrian']);

Route::get('/getkarciscontoller', [AutogenController::class, 'getkarciscontoller']);



Route::get('/print/page', [PrintController::class, 'index']);

Route::get('/unsubscribe/{user}', function (Request $request, $user) {
    if (!$request->hasValidSignature()) {
        abort(401);
    }

    return $user;
})->name('unsubscribe')->middleware('signed');



// Route::get('/buat-foto-xenter-mobile', function () {
//     $response = Http::get('http://192.168.100.100/simpeg/foto/050801141030/foto-050801141030.JPG');
//     return $response;
// });

// Route::get('/playground', function (Request $request) {
//    event(New PlaygroundEvent());

//    return null;
// });
