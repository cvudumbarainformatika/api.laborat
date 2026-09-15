<?php

namespace App\Http\Controllers\Api\SipetaKencana;

use App\Http\Controllers\Controller;
use App\Models\UserKencana;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccessLoginController extends Controller
{
    public function login_kencana(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                422
            );
        }

        $user = UserKencana::where('username', $request->username)
            ->where('aktif', 1)
            ->first();

        if (!$user) {
            return new JsonResponse([
                'message' => 'Harap periksa kembali username dan password Anda'
            ], 409);
        }

        if (!Hash::check($request->password, $user->password)) {
            return new JsonResponse([
                'message' => 'Harap periksa kembali username dan password Anda'
            ], 409);
        }

        $token = JWTAuth::fromUser($user);

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }
}
