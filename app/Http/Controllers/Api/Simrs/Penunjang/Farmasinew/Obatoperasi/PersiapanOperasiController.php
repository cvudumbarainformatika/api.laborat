<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersiapanOperasiController extends Controller
{
    //
    public function getPermintaan()
    {
        $flag = request('flag') ?? [];
        $data = PersiapanOperasi::with('rinci.obat:kd_obat,nama_obat,satuan_k', 'pasien:rs1,rs2')
            ->whereIn('flag', $flag)
            ->orderBy('tgl_permintaan', "desc")
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function simpanPermintaan(Request $request)
    {
        return new JsonResponse($request->all());
    }
    public function simpanDistribusi(Request $request)
    {
        return new JsonResponse($request->all());
    }
    public function terimaPengembalian(Request $request)
    {
        return new JsonResponse($request->all());
    }
}
