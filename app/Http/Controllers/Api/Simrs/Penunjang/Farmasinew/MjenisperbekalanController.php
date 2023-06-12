<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mjenisperbekalan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MjenisperbekalanController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mjenisperbekalan::firstOrCreate([
            'jenisperbekalan' => $request->jenisperbekalan
        ]);

        if(!$simpan){
            return new JsonResponse(['message' => 'TIDAK TERSIMPAN...!!'], 500);
        }
        return new JsonResponse(['message' => 'BERHASIL DISIMPAN', $simpan], 200);
    }

    public function list()
    {
        $list = Mjenisperbekalan::all();
        return new JsonResponse($list);
    }

    public function hapus(Request $request)
    {
        $cari = Mjenisperbekalan::where(['id' => $request->id])->get();
        if(!count($cari))
        {
            return new JsonResponse(['message' => 'DATA TIDAK DITEMUKAN....!!!'], 401);
        }

        foreach($cari as $kunci){
            $hapus = $kunci->delete();
        }

        if(!$hapus){
            return new JsonResponse(['message' => 'GAGAL DIHAPUS'], 500);
        }

            return new JsonResponse(['message' => 'BERHASIL DIHAPUS'], 200);
    }

}
