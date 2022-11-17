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
        if ($request->email !== 'sa@app.com') {
            $user = User::where('status', '=', '2')->first();

            return new JsonResponse(['message' => 'Device Reset Approved'], 205);

            $user = User::where('email', '=', $request->email)
                ->where('device', '=', $request->device)
                ->first();

            if (!$user) {
                return new JsonResponse(['message' => 'Maaf User ini belum terdaftar atau user ini sudah didaftarkan pada device yang lain'], 500);
            }
        }
        JWTAuth::factory()->setTTL(1);
        // JWTAuth::factory()->setTTL(60);
        $data = $request->only('email', 'password');
        $token = JWTAuth::attempt($data);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized', 'validator' => $data], 401);
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

    public function resetDevice(Request $request)
    {
        $user = User::find($request->id);
        $user->update([
            'device' => $request->device
        ]);

        return new JsonResponse(['message' => 'Update Device Berhasil'], 200);
    }
    public function me()
    {
        // $me = auth()->user();
        $user = JWTAuth::user();

        return new JsonResponse(['result' => $user]);
    }

    public function register(Request $request)
    {
        //username -> $req->nip
        $validator = Validator::make($request->all(), [
            'nip' => $request->nip,
            'password' => $request->password,
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = new User();
        $data->username = $request->nip;
        $data->email = $request->nip . '@app.com';
        $data->password = bcrypt($request->password);

        $saved = $data->save();

        if (!$saved) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['status' => 'success', 'message' => 'Data tersimpan'], 201);
    }

    public function logout()
    {
        auth()->logout();
        // JWTAuth::logout();
        return response()->json(['message' => 'User sukses logout dari aplikasi']);
    }
}
