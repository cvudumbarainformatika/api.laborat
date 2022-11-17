<?php

namespace App\Http\Controllers\Api\Pegawai\Master;

use App\Events\newQrEvent;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Qrcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


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

    public function qrScanned(Request $requst)
    {
        # code...
        // event(new newQrEvent($data));
    }
}
