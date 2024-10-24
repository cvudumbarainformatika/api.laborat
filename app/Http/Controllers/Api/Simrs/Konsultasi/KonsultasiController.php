<?php

namespace App\Http\Controllers\Api\Simrs\Konsultasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Mhais;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Visite\Visite;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KonsultasiController extends Controller
{
    

    public function simpandata(Request $request)
    {

      $dokter = Petugas::where('kdpegsimrs', $request->kddokterkonsul)->where('aktif', 'AKTIF')->first();

      if (!$dokter) {
        return new JsonResponse(['message' => 'Maaf Dokter Tidak Terdaftar di simrs'], 500);
      }

      $spesialis = strtoupper($dokter->statusspesialis) === 'SPESIALIS';


      $tarifKonsul = self::cekTarip($spesialis, $request);
      if (!$tarifKonsul) {
        return new JsonResponse(['message' => 'Maaf Ada error Server .... harap menghubungi IT'], 500);
      }

      // return $tarifKonsul;



      $user = FormatingHelper::session_user();
      $tglInput = date('Y-m-d H:i:s');
        
      $data=null;
      if ($request->has('id')) {
        $data = Konsultasi::find($request->id);
      } else {
        $data = new Konsultasi();
      }
       
      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->kddokterkonsul = $request->kddokterkonsul;
      $data->kduntuk = $request->kduntuk;
      $data->ketuntuk = $request->ketuntuk;
      $data->permintaan = $request->permintaan;
      $data->tgl_permintaan = $tglInput;
      $data->kdminta = $user['kodesimrs'] ?? '';
      $data->user = $user['kodesimrs'] ?? '';
      $data->save();

      // simpan tarif konsultasi select * from rs140 where rs1='".trim($_GET['noreg'])."' and rs3='".trim($_GET['kodedokter'])."' and date(rs2)='".trim($_GET['tglx'])."' and rs6='".trim($_GET['flag_biaya'])."'"
      // $konsul = Visite::where('rs1', $request->noreg)
      // ->where('rs3', $request->kddokterkonsul)
      // ->whereDate('rs2', $request->tgljawab)
      // ->where('rs6', $request->flag_biaya)
      // ->get();

      return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data], 200);
    }


    public static function cekTarip($spesialis, $request)
    {
        // select * from rs30tarif where (rs3='K5#' or rs3='K6#') 
				// and rs4 like '%|".$_GET['kd_ruang']."|%'  and rs5 like '%|".$_GET['kelas']."|%'"
        $rsx = Rstigapuluhtarif::where('rs3', 'K5#')
        ->orWhere('rs3', 'K6#')
        ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
        ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
        ->first();

        if (!$rsx) {
          return null;
        }

        $sarana=0;
				$pelayanan=0;

        if ($spesialis) {
          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
          }else if($request->kelas_ruangan=="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan=="1"){
						$sarana=$rsx->rs10;
						$pelayanan=$rsx->rs11;
					}else if($request->kelas_ruangan=="Utama"){
						$sarana=$rsx->rs12;
						$pelayanan=$rsx->rs13;
					}else if($request->kelas_ruangan=="VIP"){
						$sarana=$rsx->rs14;
						$pelayanan=$rsx->rs15;
					}else if($request->kelas_ruangan=="VVIP"){
						$sarana=$rsx->rs16;
						$pelayanan=$rsx->rs17;
					}	
        } else {
          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
					}else if($request->kelas_ruangan==="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan==="1"){
						$sarana=$rsx->rs10;
						$pelayanan=$rsx->rs11;
					}else if($request->kelas_ruangan==="Utama"){
						$sarana=$rsx->rs12;
						$pelayanan=$rsx->rs13;
					}else if($request->kelas_ruangan==="VIP"){
						$sarana=$rsx->rs14;
						$pelayanan=$rsx->rs15;
					}else if($request->kelas_ruangan==="VVIP"){
						$sarana=$rsx->rs16;
						$pelayanan=$rsx->rs17;
					}	
        }

        $tarif = (int) $sarana + (int) $pelayanan;

        return $tarif;
    }

    public function hapusdata(Request $request)
    {
        $cek = Konsultasi::find($request->id);
        if (!$cek) {
          return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }

        $hapus = $cek->delete();
        if (!$hapus) {
          return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
