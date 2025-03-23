<?php

namespace App\Http\Controllers\Api\Arsip\Master;

use App\Http\Controllers\Controller;
use App\Models\Arsip\Master\MkelasifikasiArsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MkelasifikasiController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = MkelasifikasiArsip::updateOrCreate(
            [
                'id' => $request->id
            ],
            [
                'kode' => $request->kode,
                'nama' => $request->kelasifikasi,
                'retensi' => $request->retensi
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'Data Gagal Disimpan'], 500);
        }

        return new JsonResponse(['message' => 'Data Berhasil Disimpan'], 200);
    }
}
