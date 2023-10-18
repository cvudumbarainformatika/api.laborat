<?php

use App\Helpers\Routes\RouteHelper;
use App\Http\Controllers\AutogenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Route::post('/login', [ApiController::class, 'authenticate']);
// Route::middleware(['jwt.verify'])
// ->group(function () {
//     Route::post('/logout', [ApiController::class, 'logout']);
// });

Broadcast::routes(['middleware' => 'auth:api']);



Route::prefix('v1')->group(function () {
    RouteHelper::includeRouteFiles(__DIR__ . '/v1');
});
Route::prefix('v2')->group(function () {
    RouteHelper::includeRouteFiles(__DIR__ . '/v2');
});
Route::prefix('v3')->group(function () {
    RouteHelper::includeRouteFiles(__DIR__ . '/v3'); // UNTUK MJKN
});
Route::prefix('rest')->group(function () {
    RouteHelper::includeRouteFiles(__DIR__ . '/v3'); // UNTUK MJKN
});
// Route::prefix('login')->group(function () {
//     RouteHelper::includeRouteFiles(__DIR__ . '/login');
// });

Route::post('/autogen/wawanpost', [AutogenController::class, 'wawanpost']);
