<?php

namespace App\Http\Controllers\Api\Simrs\Maping;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Maping\Mminmaxobat;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MinmaxobatController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mminmaxobat::updateOrCreate(['kd_obat' => $request->kd_obat, 'kd_ruang' => $request->kd_ruang],
            [
                'min' => $request->min,
                'max' => $request->max
            ]
        );

        if(!$simpan)
        {
            return new JsonResponse(['message' => 'DATA TIDAK TERSIMPAN...!!!'], 500);
        }
            return new JsonResponse(['message' => 'DATA TERSIMPAN...!!!'], 200);
    }

    public function listminmaxobat()
    {
        $query =  Mminmaxobat::with(['obat:rs1,rs2 as namaobat', 'ruanganx:kode,uraian as namaruangan'])
        ->paginate(request('per_page'));


        return new JsonResponse($query, 200);
    }

    public function caribynamaobat()
    {
        $id=Mruangan::where('uraian','LIKE','%' . request('r') . '%')->pluck('kode');
        $qwerty = Mminmaxobat::with(['obat:rs1,rs2 as namaobat', 'ruanganx:kode,uraian as namaruangan'])
        ->whereHas('obat', function($e){
            $e->where('rs32.rs2','LIKE', '%' . request('o') . '%');
        })
        ->whereIn('kd_ruang', $id)
        ->get();
         return new JsonResponse($qwerty, 200);
    }
}
