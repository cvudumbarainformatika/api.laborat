<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use App\Models\Simrs\Planing\Planing_Igd_ranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlannController extends Controller
{
    public function simpanranap(Request $request)
    {
       $cek = Planing_Igd_Lama::where('rs1', $request->noreg)->count();
       if($cek > 1){
        return new JsonResponse(['message' => 'Pasien Ini Sudah Dilakukan Planing']);
       }

       $wew = FormatingHelper::session_user();
       $kdpegsimrs = $wew['kodesimrs'];
       $simpan = Planing_Igd_Lama::crete(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => $request->ruangtujuan,
                'rs4' => $request->panel,
                'tgl' => 'Y-m-d H:i:s',
                'user' =>  $kdpegsimrs ?? ''
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!']);
        }

        if($request->panel === 'Rawat Inap')
        {
            $simpansambung = Planing_Igd_ranap::create(
                [
                   'noreg' => $request->noreg,
                   'norm' => $request->norm,
                   'operasi' => $request->operasi,
                   'jenisoperasi' => $request->jenisoperasi,
                   'tgloperasi' => $request->tgloperasi,
                   'ruangtujuan' => $request->ruangtujuan,
                   'keterangan' => $request->keterangan
                ]
            );
                if(!$simpansambung)
                {
                    return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!']);
                }
                return new JsonResponse(
                    [
                        'message' => 'Data Berhasil',
                        'result' => $simpansambung
                    ],200);
        }
    }
}
