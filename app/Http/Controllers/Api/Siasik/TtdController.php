<?php

namespace App\Http\Controllers\Api\Siasik;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TtdController extends Controller
{
    // public function ttdpengesahan()
    // {
    //     $pegawai = Mpegawaisimpeg::whereIn('jabatan', ['J00001','J00005','J00034','J00035','J00192'])
    //     ->where('aktif', 'AKTIF')
    //     ->select('pegawai.nip',
    //             'pegawai.nama')
    //     ->orderBy('pegawai.jabatan', 'asc')
    //     ->get();

    //     return new JsonResponse($pegawai);
    // }

    public function ttdpengesahan()
    {
        $direktur = Mpegawaisimpeg::whereIn('jabatan', ['J00001'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();

        $ppk = Mpegawaisimpeg::whereIn('jabatan', ['J00005'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();

        $bendpenerimaan = Mpegawaisimpeg::whereIn('jabatan', ['J00034'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();

        $bendpengeluaran = Mpegawaisimpeg::whereIn('jabatan', ['J00035'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();

        $verifikator = Mpegawaisimpeg::whereIn('jabatan', ['J00192'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->first();

        $data = [
            'direktur' => $direktur,
            'ppk' => $ppk,
            'bendpenerimaan' => $bendpenerimaan,
            'bendpengeluaran' => $bendpengeluaran,
            'verifikator' => $verifikator,
        ];

        return new JsonResponse($data);
    }

}
