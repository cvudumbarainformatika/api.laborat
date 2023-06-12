<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mkandungan_namagenerik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KandungannamagenerikController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mkandungan_namagenerik::firstOrCreate(['nama' => $request->nama]);
        if(!$simpan){
            return new JsonResponse(['message' => 'data gagal disimpan'], 501);
        }
        return new JsonResponse(['message' => 'data berhasil disimpan'], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mkandungan_namagenerik::where(['id' => $request->id])->get();
        if(!count($cari))
        {
            return new JsonResponse(['message' =>'data tidak ditemukan'], 401);
        }

        foreach($cari as $kunci)
        {
            $hapus = $kunci->delete();
        }

        if(!$hapus)
        {
            return new JsonResponse(['message' => 'data gagal disimpan'], 501);
        }
        return new JsonResponse(['message' => 'data berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mkandungan_namagenerik::where('nama','LIKE' ,'%'.request('q').'%')->get();
        return new JsonResponse($list);
    }
}
