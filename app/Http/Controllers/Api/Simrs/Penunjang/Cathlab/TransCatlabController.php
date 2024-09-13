<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Cathlab;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Cathlab\TransCathlab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransCatlabController extends Controller
{
    public function simpancathlab(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        $simpan = TransCathlab::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'nota' => $request->nota,
                'kd_tindakan' => $request->tindakan,
                'js' => $request->js,
                'jp' => $request->jp,
            ],
            [
                'tgl' => date('Y-m-d H:i:s'),
                'keterangan' => $request->keterangan,
                'pelaksana1' => $kdpegsimrs,
            ]
        );

        $result = TransCathlab::with(
            [
                'tarif',
                'pelaksana1',
                'pelaksana2'
            ]
        )
        ->where('id',$simpan['id'])->first();


        if(!$simpan){
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        return new JsonResponse([
            'message' =>'Data Berhasil Disimpan...!!!',
            'result' => $result
        ], 200);
    }

    public function deletecathlab(Request $request)
    {
          try {
            $cathlab = TransCathlab::where('id', $request->id)->first();

            $cathlabx = $cathlab->delete();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS...!!!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'GAGAL DIHAPUS...!!!'
            ], 500);
        }
    }
}
