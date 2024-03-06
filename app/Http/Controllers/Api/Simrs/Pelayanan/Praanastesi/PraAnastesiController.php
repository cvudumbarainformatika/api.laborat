<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Praanastesi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MpraAnastesi;
use App\Models\Simrs\Pelayanan\PraAnastesi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PraAnastesiController extends Controller
{
  public function master()
  {
     $data = MpraAnastesi::all();
     return new JsonResponse($data);
  }

  public function savedata(Request $request)
  {
      $data = null;
      if ($request->has('id')) {
        $data = PraAnastesi::find($request->id);
      } else{
        $data = new PraAnastesi();
      }

      $data->abdomen = $request->abdomen;
      $data->catatan = $request->catatan;
      $data->ekstremitas = $request->ekstremitas;
      $data->jantung = $request->jantung;
      $data->keteranganKajianSistem = $request->keteranganKajianSistem;
      $data->keteranganLaborat = $request->keteranganLaborat;
      $data->neurologi = $request->neurologi;
      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->paruparu = $request->paruparu;
      $data->perencanaan = $request->perencanaan;
      $data->skorMallampati = $request->skorMallampati;
      $data->tulangbelakang = $request->tulangbelakang;
      $data->asaClasification = $request->asaClasification;
      $data->kajianSistem = $request->kajianSistem;
      $data->laboratorium = $request->laboratorium;
      $data->penyulitAnastesi = $request->penyulitAnastesi;
      $data->pegawai_id = auth()->user()->pegawai_id;
      
      $saved = $data->save();

      if (!$saved) {
        return new JsonResponse(['message'=> 'ada kesalahan'], 500);
      }

      return new JsonResponse($data);

  }
}
