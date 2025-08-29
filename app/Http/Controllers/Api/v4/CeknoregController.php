<?php

namespace App\Http\Controllers\Api\v4;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CeknoregController extends Controller
{
  public function cek(Request $request)
  {
    $str=$request->noreg;
    // return $str;
    $decode=base64_decode($str);
    if (!$decode) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }
    $split= explode('|', $decode);
    if (count($split)<1) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }


    // return new JsonResponse($split, 200);

    $noreg=$split[0];
    $dok=$split[1] ?? null;
    $asal=$split[2] ?? null;
    $petugas=$split[3] ?? null;

    $dataPetugas = Pegawai::select('id','nip','nik','nama','foto','ttdpegawai','kdpegsimrs')->where('kdpegsimrs', $petugas)->first();
    
    $cekx=null;
    
    if ($asal === 'RAWAT JALAN') {

        $cekx = KunjunganPoli::select('rs1','rs2','rs3','rs1 as noreg', 'rs2 as norm','rs3 as tglmasuk', 'rs9', 'rs19')->where('rs1', $noreg)
        ->with(['pegawai:id,nip,nik,nama,foto,ttdpegawai,kdpegsimrs'])->first();

    } else if ($asal === 'RADIOLOGI') {

         
      $query = Transpermintaanradiologi::query();
        $cekx = $query
            ->select([
                'rs106.id',
                'rs106.rs1',
                'rs106.rs2',
                'rs106.rs1 as noreg',
                'rs106.rs2 as nota',
                 DB::raw('( CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END ) as norm'),
                
                'rs106.trmtgl as tglmasuk',
            ])
            ->leftjoin('rs17', 'rs106.rs1', '=', 'rs17.rs1') //rajal
            ->leftjoin('rs23', 'rs106.rs1', '=', 'rs23.rs1') //ranap
            ->leftjoin('rs24', 'rs24.rs1', '=', 'rs106.rs10') //ruangan ranap
            ->where('rs106.rs2', $noreg)
           
            ->first();

    } else {

         $cekx=Kunjunganranap::select(
            'rs1',
            'rs2',
            'rs1 as noreg',
            'rs2 as norm',
            'rs3 as tglmasuk',
            'rs4 as rs3',
            'rs10')->where('rs1', $noreg)
            ->with(['pegawai:id,nip,nik,nama,foto,ttdpegawai,kdpegsimrs'])->first();
      
    }


    // return $cekx;
    if (!$cekx) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }

    $ata =[
      'noreg'=>$noreg,
      'dok'=>$dok,
      'asal'=>$asal,
      'petugas'=>$cekx,
      'dataPetugas'=>$dataPetugas
    ];
    return new JsonResponse($ata);
  }
}
