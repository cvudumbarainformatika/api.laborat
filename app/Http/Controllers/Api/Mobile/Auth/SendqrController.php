<?php

namespace App\Http\Controllers\Api\Mobile\Auth;

use App\Events\LoginQrEvent;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SendqrController extends Controller
{
    public function data(Request $request)
    {
        $user = JWTAuth::user();
        $message = [
            'menu' => 'login-qr',
            'data' => $request->qr,
            'email' => $user->email,
            'token' => $request->token
        ];
        event(new LoginQrEvent($message));
        return response()->json($request->qr);
    }

    // ganti status
    // null, '' = bisa loagin, 8=tidak bisa scan barcode, 9= tidak bisa scan barcode dan wajah
}
