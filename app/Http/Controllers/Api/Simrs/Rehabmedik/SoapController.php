<?php

namespace App\Http\Controllers\Api\Simrs\Rehabmedik;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Fisioterapi\FisioAsessment;
use App\Models\Simrs\Penunjang\Fisioterapi\FisioSoap;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoapController extends Controller
{
  public function store(Request $request): JsonResponse
  {
    $data = null;

    $auth = auth()->user()->pegawai_id;
    $pegawai = Pegawai::find($auth);

    $awal = 0;
    $hitung = 0;

    try {
      if ($request->has('id')) {
        $data = FisioSoap::find($request->id);
        $awal = $data->awal;
        $hitung = $data->urut;
      } else {
        $data = new FisioSoap();
        $count = FisioAsessment::where('noreg', $request->noreg)->count();
        if ($count === 0) {
          $awal = 1;
        }
        $hitung = $count++;
      }








      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->tgl = date('Y-m-d H:i:s');
      $data->subjective = $request->subjective;
      $data->objective = $request->objective;
      $data->asessment = $request->asessment;
      $data->planning = $request->planning;
      $data->goal = $request->goal;
      $data->tindakan = $request->tindakan;
      $data->frekuensi = $request->frekuensi;
      $data->rencana = $request->rencana;
      $data->procedure = $request->procedure;
      $data->awal = $awal;
      $data->urut = $hitung;
      $data->nakes = $pegawai->kdgroupnakes;
      $data->users = $pegawai->kdpegsimrs;
      $data->save();



      return new JsonResponse($data, 200);
    } catch (\Throwable $th) {
      //throw $th;
      return new JsonResponse($th->getMessage(), 500);
    }
  }

  public function delete(Request $request)
  {
    $data = FisioAsessment::find($request->id);
    if (!$data) {
      return new JsonResponse(['message' => 'Data gagal dihapus'], 410);
    }
    $data->delete();
    return new JsonResponse(['message' => 'Data sudah dihapus'], 200);
  }
}
