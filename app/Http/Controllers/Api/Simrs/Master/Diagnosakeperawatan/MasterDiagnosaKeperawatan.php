<?php

namespace App\Http\Controllers\Api\Simrs\Master\Diagnosakeperawatan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use App\Models\Simrs\Master\Mtemplategambar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterDiagnosaKeperawatan extends Controller
{

    public function store(Request $request)
    {
        $data = Mdiagnosakeperawatan::create(
            [
                'kode' => $request->kode,
                'nama' => $request->nama,
            ]
        );

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
        }

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan...!!!',
            'result' => $data
        ], 200);
    }
}
