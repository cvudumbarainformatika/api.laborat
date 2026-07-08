<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\MppSkrining;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MppController extends Controller
{
    public function list()
    {
        $data = MppSkrining::where('noreg', request('noreg'))
            ->with('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto')
            ->orderBy('created_at', 'DESC')
            ->get();
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $pegawai ? $pegawai->kdpegsimrs : null;

        $data = null;
        if ($request->id === null || !$request->has('id')) {
            $data = new MppSkrining();
        } else {
            $data = MppSkrining::find($request->id);
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 444);
            }
        }

        $data->fill($request->except(['id', 'kdpegsimrs']));
        $data->kdpegsimrs = $kdpegsimrs;
        $data->save();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'result' => $data->load('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto')
        ], 200);
    }

    public function hapusdata(Request $request)
    {
        $data = MppSkrining::find($request->id);
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
