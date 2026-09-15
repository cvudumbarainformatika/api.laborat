<?php

use App\Http\Controllers\Api\SipetaKencana\AccessLoginController as SipetaKencanaAccessLoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login_kencana', [SipetaKencanaAccessLoginController::class, 'login_kencana']);
