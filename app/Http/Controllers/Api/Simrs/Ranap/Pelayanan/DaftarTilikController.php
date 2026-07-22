<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Pelayanan\DaftarTilik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DaftarTilikController extends Controller
{
    public function list()
    {
        $data = DaftarTilik::with([
            'petugas_pre_pengantar:id,nama,nik,kdpegsimrs,kdgroupnakes',
            'petugas_pre_penerima:id,nama,nik,kdpegsimrs,kdgroupnakes',
            'petugas_paska_pengantar:id,nama,nik,kdpegsimrs,kdgroupnakes',
            'petugas_paska_penerima:id,nama,nik,kdpegsimrs,kdgroupnakes'
        ])
            ->where('noreg', request('noreg'))
            ->orderBy('created_at', 'DESC')
            ->get();
            
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {
        $data = null;
        if ($request->id) {
            $data = DaftarTilik::find($request->id);
        }

        if ($data) {
            $data->fill($request->except(['id']));
        } else {
            $data = new DaftarTilik();
            $data->fill($request->except(['id']));
        }
        
        $data->save();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'result' => $data
        ], 200);
    }

    public function hapusdata(Request $request)
    {
        $data = DaftarTilik::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 444);
        }
        
        $data->delete();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }
}
