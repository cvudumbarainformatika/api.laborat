<?php

namespace App\Http\Controllers\Api\Mobile\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
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

            $user = User::where('email', '=', $request->email)
                ->where('device', '=', $request->device)
                ->first();
            $temp = User::where('email', '=', $request->email)
                ->first();

            // return new JsonResponse(['message' => $user], 205);
            if ($temp->status === '2') {
                return new JsonResponse(['message' => 'Device Reset Approved'], 410);
            }

            if (!$user) {
                return new JsonResponse(['message' => 'Maaf User ini belum terdaftar atau user ini sudah didaftarkan pada device yang lain'], 406);
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
        $pegawai = Pegawai::with('user', 'jadwal')->find($user->pegawai_id);

        return new JsonResponse(['result' => $pegawai]);
    }

    public function register(Request $request)
    {
        //username -> $req->nip
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = new User();
        $data->username = $request->username;
        $data->email = $request->username . '@app.com';
        $data->password = bcrypt($request->password);
        $data->pegawai_id = $request->pegawai_id;
        $data->device = $request->device;
        $data->nama = $request->nama;

        $saved = $data->save();

        if (!$saved) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Ada Kesalahan'], 500);
        }
        $pegawai = Pegawai::find($request->pegawai_id);
        $pegawai->update([
            'pass' => $request->password
        ]);
        $data->load('pegawai');
        return new JsonResponse(['status' => 'success', 'message' => 'Data tersimpan', 'user' => $data], 201);
    }

    public function logout()
    {
        auth()->logout();
        // JWTAuth::logout();
        return response()->json(['message' => 'User sukses logout dari aplikasi']);
    }
}
