<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaanfisikController extends Controller
{
    public function simpan(Request $request)
    {
        $simpanperiksaan = Pemeriksaanfisik::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->denyutjantung,
                'pernapasan' => $request->pernapasan,
                'sistole' => $request->sistole,
                'diastole' => $request->diastole,
                'suhutubuh' => $request->suhutubuh,
                'statuspsikologis' => $request->statuspsikologis,
                'sosialekonomi' => $request->sosialekonomi,
                'spiritual' => $request->spiritual,
                'user'  => auth()->user()->pegawai_id,
            ]
        );



        return new JsonResponse($request->all());
    }
}
