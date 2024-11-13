<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use App\Models\Simrs\Planing\Planing_Igd_Pulang;
use App\Models\Simrs\Planing\Planing_Igd_ranap;
use App\Models\Simrs\Planing\Planing_Igd_Rujukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlannController extends Controller
{
    public function simpanranap(Request $request)
    {
    //     $data = Planing_Igd_Lama::with(
    //         [
    //             'planranap' => function($planranap){
    //                 $planranap->with(
    //                     [
    //                         'ruangranap'
    //                     ]
    //                 );
    //             },
    //             'planrujukan',
    //             'planpulang'
    //         ]
    //     )->where('rs1', $request->noreg)->get();
    //     return new JsonResponse(
    //         [
    //             'message' => 'Data Berhasil Disimpan',
    //             'result' => $data
    //         ],
    //     200);
        $cek = Planing_Igd_Lama::where('rs1', $request->noreg)->count();
       if($cek > 0){
        return new JsonResponse(['message' => 'Pasien Ini Sudah Dilakukan Planing'],500);
       }

       DB::beginTransaction();
       try {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $simpan = Planing_Igd_Lama::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => 'POL014',
                    'rs4' => $request->panel,
                    'rs5' => $request->ruangtujuan ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' =>  $kdpegsimrs ?? ''
                ]
            );

            if($request->panel === 'Rawat Inap')
            {
                $simpansambung = Planing_Igd_ranap::create(
                    [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $simpan['id'] ?? '',
                    'operasi' => $request->operasi,
                    'jenisoperasi' => $request->jenisoperasi,
                    'tgloperasi' => $request->tgloperasi,
                    'ruangtujuan' => $request->ruangtujuan,
                    'keterangan' => $request->keterangan
                    ]
                );
            }else if($request->panel === 'Rujuk Ke Rumah Sakit Lain')
            {

                $simpansambung = Planing_Igd_Rujukan::create(
                    [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $simpan['id'] ?? '',
                    'atas_dasar' => $request->atasdasar,
                    'jenis_pelayanan' => $request->jenispelayanan,
                    'tgl_rujukan' => $request->tglrujukan,
                    'tgl_rencana_kunjungan' => $request->tglrencanakunjungan,
                    'type_faskes' => $request->typefaskes,
                    'koders' => $request->koders,
                    'di_rujuk_ke' => $request->dirujukkers,
                    'kodepoli' => $request->kodepoli,
                    'poli_rujukan' => $request->polirujukan,
                    'keterangan' => $request->keterangan,
                    ]
                );


            }else if($request->panel === 'Pulang')
            {
                $simpansambung = Planing_Igd_Pulang::create(
                    [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $simpan['id'] ?? '',
                    'atas_dasar' => $request->atasdasarpulang,
                    'tgl_meninggal' => $request->tglmeninggal,
                    'jam_meninggal' => $request->jammeninggal,
                    'alasan_meninggal' => $request->alasanmeninggal,
                    ]
                );
            }


            DB::commit();
            $data = Planing_Igd_Lama::with(
                [
                    'planranap' => function($planranap){
                        $planranap->with(
                            [
                                'ruangranap'
                            ]
                        );
                    },
                    'planrujukan',
                    'planpulang'
                ]
            )->where('rs1', $request->noreg)->get();

            return new JsonResponse(
                [
                    'message' => 'Data Berhasil Disimpan',
                    'result' => $data
                ],
            200);
        }catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => 'err' . $e
            ], 410);
        }
    }
}
