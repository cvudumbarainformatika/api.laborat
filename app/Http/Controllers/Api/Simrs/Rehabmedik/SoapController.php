<?php

namespace App\Http\Controllers\Api\Simrs\Rehabmedik;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\MFisioFrekuensi;
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

  public function masterFrekuensi()
  {
    $data = cache()->remember('m_fisio_frekuensi', now()->addHours(8), function () {
      return MFisioFrekuensi::all();
    });

    return new JsonResponse([
      'message' => 'success',
      'result' => $data
    ], 200);
  }
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
        $count = FisioSoap::where('noreg', $request->noreg)->count();
        $cekAwal = FisioSoap::where('noreg', $request->noreg)->where('awal', 1)->count();
        $awal = $cekAwal === 0 ? 1 : 0;
        $hitung = (int)$count + 1;
      }

      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      // $data->tgl = date('Y-m-d H:i:s');
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
      $data->user = $pegawai->kdpegsimrs;
      $data->save();

      return new JsonResponse($data->load('petugas:kdpegsimrs,kdgroupnakes,nama,nik'), 200);
    } catch (\Throwable $th) {
      //throw $th;
      return new JsonResponse($th->getMessage(), 500);
    }
  }

  public function delete(Request $request)
  {
    $data = FisioSoap::find($request->id);
    if (!$data) {
      return new JsonResponse(['message' => 'Data gagal dihapus'], 410);
    }
    $data->delete();
    return new JsonResponse(['message' => 'Data sudah dihapus'], 200);
  }
}
