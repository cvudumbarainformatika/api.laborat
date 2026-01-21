<?php

namespace App\Http\Controllers\Api\Simrs\Rehabmedik;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Fisioterapi\FisioAsessment;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengkajianController extends Controller
{
  public function store(Request $request): JsonResponse
  {
    $data = null;

    $auth = auth()->user()->pegawai_id;
    $pegawai = Pegawai::find($auth);

    try {
      if ($request->has('id')) {
        $data = FisioAsessment::find($request->id);
      } else {
        $data = new FisioAsessment();
      }



      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->tgl = date('Y-m-d H:i:s');
      $data->keluhan_utama = $request->keluhan_utama;
      $data->riwayat_penyakit_sekarang = $request->riwayat_penyakit_sekarang;
      $data->riwayat_penyakit_dahulu = $request->riwayat_penyakit_dahulu;
      $data->keadaan_umum = $request->keadaan_umum;
      $data->vas = $request->vas;
      $data->neurologis = $request->neurologis;
      $data->muskuloskeletal = $request->muskuloskeletal;
      $data->pencitraan = $request->pencitraan;
      $data->lain_lain = $request->lain_lain;
      $data->diagnosis_fungsional = $request->diagnosis_fungsional;
      $data->problem_rehabilitasimedik = $request->problem_rehabilitasimedik;
      $data->problem_rehabilitasimedik_lain = $request->problem_rehabilitasimedik_lain;
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
