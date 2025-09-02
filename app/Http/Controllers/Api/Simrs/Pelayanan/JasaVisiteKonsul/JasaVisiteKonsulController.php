<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\JasaVisiteKonsul;

use App\Helpers\CekTarifHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JasaVisiteKonsulController extends Controller
{

  public function index()
  {

     $query = Visite::query();
     $query->leftJoin('kepegx.pegawai as pegawai', 'rs140.rs3', '=', 'pegawai.kdpegsimrs')
        ->leftJoin('rs30tarif as tarip', 'rs140.rs6', '=', 'tarip.rs3');
     $data = $query->select('rs140.*', 'pegawai.nama as dokter','tarip.rs2 as namatarif')
      ->where(function ($q) {
        $q->where('rs140.rs1', '=', request('noreg'))
            ->where('rs140.rs8', '=', request('kdgroup_ruangan'));
     })
     ->orderBy('rs140.rs2', 'desc')
     ->get();
     
     return new JsonResponse($data);
  }
  public function simpan(Request $request)
  {

    $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

    if (count($cekKasir) > 0) {
      return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
    }


    $dokter = Petugas::where('kdpegsimrs', $request->kddokter)->where('aktif', 'AKTIF')->first();

    if (!$dokter) {
      return new JsonResponse(['message' => 'Maaf Dokter Tidak Terdaftar di simrs'], 500);
    }

    $spesialis = (strtoupper($dokter->statusspesialis) === 'SPESIALIS' || strtoupper($dokter->statusspesialis) === 'SUB SPESIALIS');

    $tarif = null;
    if ($request->jenis === 'VISITE') {
      $tarif = CekTarifHelper::cekTaripVisite($spesialis, $request, $dokter);
    } else {
      $tarif = CekTarifHelper::cekTaripKonsul($spesialis, $request, $dokter);
    }

    if (!$tarif) {
      return new JsonResponse(['message' => "Maaf ... Ada Kesalahan Pada Tarif $request->jenis"], 500);
    }
    
    $kdpegsimrs = $request->kddokter;
    $cekTarif = Visite::select('rs1')
      ->where('rs1', $request->noreg)
      ->where('rs3', $kdpegsimrs)
      ->where('rs2', 'LIKE', '%'.date('Y-m-d').'%')
      ->where('rs6', $tarif['flag_biaya'])
      ->get();

      $hari_ini = date('Y-m-d H:i:s');
    if (count($cekTarif) === 0) {

      Visite::create([
        'rs1' => $request->noreg,
        'rs2' => $hari_ini,
        'rs3' => $kdpegsimrs,
        'rs4' => $tarif['sarana'],
        'rs5' => $tarif['pelayanan'],
        'rs6' => $tarif['flag_biaya'],
        'rs8' => $request->kdgroup_ruangan,
        'rs9' => $request->kodesistembayar
      ]);
    }

    return new JsonResponse($tarif);
  }

  function hapus(Request $request){



    $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

    if (count($cekKasir) > 0) {
      return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
    }

    $data = Visite::find($request->id);
    if (!$data) {
      return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
    }
    $hapus = $data->delete();
    if (!$hapus) {
      return new JsonResponse(['message' => 'gagal dihapus'], 501);
    }
    return new JsonResponse(
        [
            'message' => 'data berhasil dihapus'
        ], 
    200);
  }
  
}
