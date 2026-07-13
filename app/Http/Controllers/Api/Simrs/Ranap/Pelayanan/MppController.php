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
            ->with([
                'petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'petugas_updated:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'
            ])
            ->orderBy('created_at', 'DESC')
            ->get();
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $pegawai ? $pegawai->kdpegsimrs : null;

        $data = MppSkrining::where('noreg', $request->noreg)->first();
        if ($data) {
            $data->fill($request->except(['id', 'kdpegsimrs', 'kdpegsimrs_updated']));
            $data->kdpegsimrs_updated = $kdpegsimrs;
        } else {
            $data = new MppSkrining();
            $data->fill($request->except(['id', 'kdpegsimrs', 'kdpegsimrs_updated']));
            $data->kdpegsimrs = $kdpegsimrs;
        }
        $data->save();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'result' => $data->load([
                'petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'petugas_updated:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'
            ])
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
