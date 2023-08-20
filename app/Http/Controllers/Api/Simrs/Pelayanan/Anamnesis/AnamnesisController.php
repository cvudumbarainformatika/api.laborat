<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Anamnesis\Anamnesis as AnamnesisAnamnesis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        $simpananamnesis = AnamnesisAnamnesis::firstOrCreate(['rs1' => $request->noreg],
        [
            'rs2' => $request->norm,
            'rs3' => date('Y-m-d H:i:s'),
            'rs4' => $request->keluhanutama,
            'riwayatpenyakit' => $request->riwayatpenyakit,
            'riwayatalergi' => $request->riwayatalergi,
            'riwayatpengobatan' => $request->riwayatpengobatan,
        ]);
        if(!$simpananamnesis)
        {
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }
        return new JsonResponse(['message' => 'BERHASIL DISIMPAN', $simpananamnesis], 200);
    }
}
