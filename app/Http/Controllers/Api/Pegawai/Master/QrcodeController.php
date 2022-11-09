<?php

namespace App\Http\Controllers\Api\Pegawai\Master;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Qrcode as PegawaiQrcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrcodeController extends Controller
{
    //

    public function getQr(Request $request)
    {
    }
    public function createQr(Request $request)
    {
        $ip = $request->ip();
        $nama = $ip . 'apem pasundan';
        $path = QrCode::format('svg')
            // ->generate($nama)->store('image', 'public');
            ->generate($nama, public_path('qr/' . $nama . '.svg'));
        // ->generate($nama, '.svg');
        if ($path) {
            return new JsonResponse(['message' => 'Qr Code Gagal Disimpan', 'path' => $path], 500);
        }
        $data = PegawaiQrcode::create([
            'ip' => $ip,
            'code' => $nama,
            'path' => 'qr/' . $nama . '.svg'
        ]);
        return new JsonResponse($data, 200);
    }
}
