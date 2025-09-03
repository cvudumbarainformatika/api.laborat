<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\jasaKeperawatan;

use App\Helpers\CekTarifHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Keperawatan\Keperawatan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JasaKeperawatanController extends Controller
{

  public function index()
  {

    $data = Keperawatan::query()
            // ->leftJoin('kepegx.pegawai as pegawai', 'rs203.rs3', '=', 'pegawai.kdpegsimrs')
            ->leftJoin('rs30tarif as tarip', 'rs203.rs3', '=', 'tarip.rs1')
            ->leftJoin('rs24 as ruangan', 'rs203.rs8', '=', 'ruangan.rs4')
            ->select('rs203.*','ruangan.rs5 as ruang','tarip.rs2 as namatarif')
            ->where(function ($q) {
        $q->where('rs203.rs1', '=', request('noreg'));
            // ->where('rs203.rs8', '=', request('kdgroup_ruangan'));
     })
     ->orderBy('rs203.rs2', 'desc')
     ->distinct()
     ->get();
     
     return new JsonResponse($data);
     
  }


public function getTarif(Request $request)
{
  

    $kdRuang = $request->get('kd_ruang');
    $kelas   = $request->get('kelas');
    $term    = $request->get('q');

    // Query utama
    $data = CekTarifHelper::cekTarifKeperawatan($kdRuang, $kelas);

    return response()->json($data);
}





  public function simpan(Request $request)
  {

    $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

    if (count($cekKasir) > 0) {
      return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
    }

    $user = FormatingHelper::session_user();

    $jasa = Keperawatan::create(
      [
        'rs1' => $request->noreg,
        'rs2' => $request->tanggal. ' ' . date('H:i:s'),
        'rs3' => $request->kode_biaya,
        'rs4' => 0,
        'rs5' => $request->tarif,
        'rs8' => $request->kdgroup_ruangan,
        'rs9' => $request->kodesistembayar,
        'rs11' => $request->kdgroup_ruangan,
        'rs12' => $user['kodesimrs'],
      ]
      );
    return new JsonResponse($jasa);
  }

  function hapus(Request $request){



    $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

    if (count($cekKasir) > 0) {
      return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
    }

    $data = Keperawatan::find($request->id);
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
