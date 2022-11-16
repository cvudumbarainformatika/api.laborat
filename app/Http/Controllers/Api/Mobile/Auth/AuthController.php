<?php

namespace App\Http\Controllers\Api\Mobile\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = User::where('email', '=', $request->email)
            ->where('device', '=', $request->device)
            ->first();
        if (!$user) {
            return new JsonResponse(['message' => 'Maaf User ini belum terdaftar atau user ini sudah didaftarkan pada device yang lain'], 500);
        }
        JWTAuth::factory()->setTTL(60);
        $token = JWTAuth::attempt($validator->validated());
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'token' => $token,
            'user' => auth()->user()
        ]);
    }
}
