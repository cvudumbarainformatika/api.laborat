<?php

namespace App\Http\Controllers\Api\Pegawai\Master;

use App\Events\newQrEvent;
use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Qrcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class QrcodeController extends Controller
{
    //

    public function getQr(Request $request)
    {
        $data = Qrcode::latest()->first();
        return new JsonResponse($data, 200);
    }

    public function createQr(Request $request)
    {
        $ip = $request->id;
        $date = date('Y-m-d H:i:s');
        $nama = $ip . '#' . $date;

        $data = Qrcode::updateOrCreate([
            'ip' => $ip,
        ], [
            'code' => $nama,
            'path' => $date
        ]);
        return new JsonResponse($data, 201);
    }
    public function updateQr($ip)
    {
        // $ip = $ip;
        $date = date('Y-m-d H:i:s');
        $nama = $ip . '#' . $date;

        $data = Qrcode::updateOrCreate([
            'ip' => $ip,
        ], [
            'code' => $nama,
            'path' => $date
        ]);
        event(new newQrEvent($data));
        // return new JsonResponse($data, 201);
    }

    public function qrScanned(Request $request)
    {
        $temp = explode('#', $request->qr);
        $data = Qrcode::where('ip', $temp[0])->first();
        if ($data->path === $temp[1]) {
            $this->updateQr($temp[0]);
            $user = JWTAuth::user();
            $jadwal = JadwalController::toMatch($user->id);

            return new JsonResponse([
                'message' => 'cari jadwal',
                'user' => $user,
                'jadwal' => $jadwal,
            ], 200);
        } else {
            return new JsonResponse(['message' => 'qr Code Expired'], 401);
        }
        return new JsonResponse($data, 200);
    }
}
