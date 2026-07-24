<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\PraSedasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PraSedasiController extends Controller
{
    public function list()
    {
        $data = PraSedasi::where('noreg', request('noreg'))
            ->with([
                'petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'
            ])
            ->orderBy('id', 'DESC')
            ->get();

        return new JsonResponse($data, 200);
    }

    public function simpandata(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $pegawai ? $pegawai->kdpegsimrs : null;

        $data = null;
        if ($request->has('id') && $request->id) {
            $data = PraSedasi::find($request->id);
        }

        if (!$data) {
            $data = new PraSedasi();
            $data->kdpegsimrs = $kdpegsimrs;
        }

        $payload = $request->except(['id', 'created_at', 'updated_at']);

        // Ensure array fields are formatted as JSON text for MySQL 5.5
        $jsonFields = ['kajian_sistem', 'laboratorium', 'diagnosis', 'penyulit_sedasi_lain', 'teknik_khusus'];
        foreach ($jsonFields as $field) {
            if (isset($payload[$field]) && is_array($payload[$field])) {
                $payload[$field] = json_encode($payload[$field]);
            }
        }

        $data->fill($payload);
        $data->tgl = date('Y-m-d H:i:s');
        $data->save();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data Pra Sedasi Berhasil Disimpan',
            'result' => $data->load(['petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'])
        ], 200);
    }

    public function hapusdata(Request $request)
    {
        $data = PraSedasi::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 444);
        }
        $data->delete();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data Pra Sedasi Berhasil Dihapus'
        ], 200);
    }
}
