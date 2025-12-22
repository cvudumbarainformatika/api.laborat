<?php

use App\Http\Controllers\Api\Aset\Master\MasetContoller;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master/maset'
], function () {
    Route::get('/index', [MasetContoller::class, 'index']);
    Route::post('/simpan', [MasetContoller::class, 'store']);
    Route::post('/delete', [MasetContoller::class, 'delete']);

});
