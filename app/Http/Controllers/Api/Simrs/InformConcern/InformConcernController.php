<?php

namespace App\Http\Controllers\Api\Simrs\InformConcern;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\InformConcern\InformConcern;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Mhais;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Visite\Visite;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Polyfill\Intl\Idn\Info;

class InformConcernController extends Controller
{
    

    public function simpandata(Request $request)
    {

      
      $data=null;
      if ($request->has('id')) {
        $data = InformConcern::find($request->id);
      } else {
        $data = new InformConcern();
      }
       
      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->tgl = date('Y-m-d H:i:s');
      $data->tanggal = $request->tanggal;
      $data->pelaksana = $request->pelaksana;
      $data->pengedukasi = $request->pengedukasi;
      $data->penerimaEdukasi = $request->penerimaEdukasi;
      $data->diagnosis = $request->diagnosis;
      $data->dasarDiagnosis = $request->dasarDiagnosis;
      $data->tindakanMedis = $request->tindakanMedis;
      $data->indikasi = $request->indikasi;
      $data->tujuan = $request->tujuan;
      $data->tujuanLain = $request->tujuanLain;
      $data->tatacara = $request->tatacara;
      $data->resiko = $request->resiko;
      $data->resikoLain = $request->resikoLain;
      $data->komplikasi = $request->komplikasi;
      $data->prognosis = $request->prognosis;
      $data->alternatif = $request->alternatif;
      $data->ttdPetugas = $request->ttdPetugas;
      $data->ttdPasien = $request->ttdPasien;
      $data->hubunganDgPasien = $request->hubunganDgPasien;
      $data->keluarga = $request->keluarga;
      $data->nama = $request->nama;
      $data->lp = $request->lp;
      $data->tglLahir = $request->tglLahir;
      $data->noKtp = $request->noKtp;
      $data->alamat = $request->alamat;
      $data->telepon = $request->telepon;
      $data->ttdDokter = $request->ttdDokter;
      $data->ttdSaksiRs = $request->ttdSaksiRs;
      $data->ttdSaksiPasien = $request->ttdSaksiPasien;
      $data->ttdygMenyatakan = $request->ttdygMenyatakan;
      $data->kdDokter = $request->kdDokter;
      $data->kdPetugas = $request->kdPetugas;
      $data->saksiPasien = $request->saksiPasien;
      $data->setuju = $request->setuju;
      $data->kdRuang = $request->kdRuang;
      $data->jenis = $request->jenis;
      $data->user = $user['kodesimrs'] ?? '';
      $data->save();

      return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data], 200);
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

    

    public function getdatarkd()
    {
      $user = FormatingHelper::session_user();
       $data = Konsultasi::selectRaw('
        id,noreg,norm,flag,kddokterkonsul,ketuntuk,permintaan,tgl_permintaan,jawaban,tgl_jawaban,kdminta,user
       ')->where('kddokterkonsul', $user['kodesimrs'])
       ->with([
        'dokterkonsul'=>function($q){
          $q->select('nama','kdpegsimrs','nip','nik','foto','aktif')
          ->where('aktif','AKTIF');
        },
        'nakesminta'=>function($q){
          $q->select('nama','kdpegsimrs','nip','nik','foto','aktif')
          ->where('aktif','AKTIF');
        },
        'userinput'=>function($q){
          $q->select('nama','kdpegsimrs','nip','nik','foto','aktif')
          ->where('aktif','AKTIF');
        },
        'kunjunganranap'=>function($q){
          $q->select(
            'rs23.rs1','rs23.rs2','rs23.rs3','rs23.rs5','rs23.rs41 as statuspulang', 'rs15.rs2 as nama','rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelas_ruangan',
            'rs24.rs4 as kdgroup_ruangan',
            )
            ->leftJoin('rs15','rs15.rs1','rs23.rs2')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->with([
              'diagnosamedis'=>function($q){
                $q->with('masterdiagnosa')
                ->where('rs13', '!=', 'POL014');
              }
            ])
            ;
        },
        'kunjunganpoli'=>function($q){
          $q->select(
            'rs17.rs1','rs17.rs2','rs17.rs3','rs17.rs8','rs17.rs19 as statuspulang', 'rs15.rs2 as nama',
            )
            ->leftJoin('rs15','rs15.rs1','rs17.rs2')
            ->where('rs17.rs8', '!=', 'POL014')
            ;
        },
        'kunjunganigd'=>function($q){
          $q->select(
            'rs17.rs1','rs17.rs2','rs17.rs3','rs17.rs8','rs17.rs19 as statuspulang', 'rs15.rs2 as nama',
            )
            ->leftJoin('rs15','rs15.rs1','rs17.rs2')
            ->where('rs17.rs8', '=', 'POL014')
            ;
        },

       ])
       ->orderBy('id', 'desc')
       ->paginate(50);

       return response()->json($data);
    }

    public function updateFlag(Request $request)
    {
       $data = Konsultasi::find($request->id);
       $data->flag = '1';
       $data->save();
    }
    public function updateJawaban(Request $request)
    {

      $user = FormatingHelper::session_user();

      $dokter = Petugas::where('kdpegsimrs', $user['kodesimrs'])->where('aktif', 'AKTIF')->first();

      if (!$dokter) {
        return new JsonResponse(['message' => 'Maaf Dokter Tidak Terdaftar di simrs'], 500);
      }

      $spesialis = strtoupper($dokter->statusspesialis) === 'SPESIALIS';

      // cek tarif
      $tarifKonsul = self::cekTarip($spesialis, $request);
      if (!$tarifKonsul) {
        return new JsonResponse(['message' => 'Maaf Ada error Server .... harap menghubungi IT'], 500);
      }

      $data = Konsultasi::find($request->id);
      if (!$data) {
        return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
      }

      $tglJawab = $data->tgl_jawaban;

      if ($tglJawab === null || $tglJawab === '0000-00-00 00:00:00' || $tglJawab === '') {

        $hari_ini = date('Y-m-d H:i:s');
        $data->tgl_jawaban = $hari_ini;

        // cari tarif dokter dan masukkan ke tarif jika dlm 1 hari belum ada data masuk

        //cek data tarif harini untuk dokter
        $cekTarif = Visite::select('*')
        ->where('rs1', $request->noreg)
        ->where('rs3', $dokter->kdpegsimrs)
        ->where('rs2', 'LIKE', '%'.date('Y-m-d').'%')
        ->where('rs6', $tarifKonsul['flag_biaya'])
        ->get();

        // jika billing belum masuk
        if (count($cekTarif) === 0) {

          Visite::create([
            'rs1' => $request->noreg,
            'rs2' => $hari_ini,
            'rs3' => $dokter->kdpegsimrs,
            'rs4' => $tarifKonsul['sarana'],
            'rs5' => $tarifKonsul['pelayanan'],
            'rs6' => $tarifKonsul['flag_biaya'],
            'rs8' => $request->kdgroup_ruangan,
            'rs9' => $request->kodesistembayar
          ]);
        }

      }



       
      $data->flag = '2';
      
      $data->jawaban = $request->jawaban;
      $data->kdruang = $request->kdruang;
      $data->save();

      return new JsonResponse(['message' => 'Jawaban tersimpan', 'result' => $data], 200);
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
        $flag_biaya=$rsx->rs3;

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

        return [
          'flag_biaya' => $flag_biaya,
          'tarif' => $tarif,
          'sarana' => $sarana,
          'pelayanan' => $pelayanan
        ];
    }
}
