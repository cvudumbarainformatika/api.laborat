<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\AsuhanKeperawatanPerioperatif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsuhanKeperawatanController extends Controller
{
    //
    public function getdata()
    {
        $data = AsuhanKeperawatanPerioperatif::where('noreg', request('noreg'))
            ->where('norm', request('norm'))
            ->where('nota', request('nota'))
            ->first();
        return new JsonResponse(['data' => $data]);
    }
    public function simpan(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'norm' => 'required',
        ]);

        $result = AsuhanKeperawatanPerioperatif::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'nota' => $request->nota,
                'norm' => $request->norm,
            ],
            $request->all()
        );
        if (!$result) return new JsonResponse(['message' => 'Data gagal disimpan'], 410);

        return new JsonResponse([
            'message' => 'Data berhasil disimpan'
        ]);
    }
}
