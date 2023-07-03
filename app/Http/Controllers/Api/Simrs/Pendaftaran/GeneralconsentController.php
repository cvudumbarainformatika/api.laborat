<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Generalconsenttrans_h;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralconsentController extends Controller
{
    public function mastergeneralconsent()
    {
        $data = Mgeneralconsent::all();
        return new JsonResponse($data);
    }

    public function simpangeneralcontent(Request $request)
    {
        try{
            DB::beginTransaction();

           $simpangeneralcontent = Generalconsenttrans_h::updateOrcreate(['norm' => $request->norm],[
            'tanggal' => $request->tanggal,
            'jam' => $request->jam,
            'penanggungjawab' => $request->penanggungjawab,
            'petugas' => $request->petugas,
            'user_entry' => auth()->user()->id,
           ]);
            $simpanrinci = $simpangeneralcontent->hederrinci()->updateOrcreate([ 'kd_pernyataan' => $request->kd_pernyataan],
            [
                'jawaban' => $request->jawaban
            ]);
            DB::commit();
            return new JsonResponse(
                [
                    'message' => 'DATA TERSIMPAN...!!!', $simpangeneralcontent,$simpanrinci
                ],
                200
            );
        }catch (\Exception $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['Gagal tersimpan' => $th], 500);
        }
    }

}
