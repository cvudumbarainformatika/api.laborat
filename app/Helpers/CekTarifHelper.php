<?php

namespace App\Helpers;

use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class CekTarifHelper
{
    
    public static function cekTaripKonsul($spesialis, $request, $pegawai)
    {
        $rs = null;


        if ($spesialis) {
            $kelasIC = in_array($request->kelas_ruangan, ["IC", "ICC", "NICU", "PICU"]);
            $status  = strtoupper($pegawai->statusspesialis);

            if ($kelasIC) {
                $kode = $status === 'SPESIALIS' ? 'K9#' : ($status === 'SUB SPESIALIS' ? 'K10#' : 'K9#');
            } else {
                $kode = $status === 'SPESIALIS' ? 'K5#' : ($status === 'SUB SPESIALIS' ? 'K11#' : 'K5#');
            }

            $rs = $kode
                ? Rstigapuluhtarif::where('rs3', $kode)
                    ->where('rs4', 'like', "%|{$request->kdgroup_ruangan}|%")
                    ->where('rs5', 'like', "%|{$request->kelas_ruangan}|%")
                    ->first()
                : null;

            
        } else {
            $kelasIC = in_array($request->kelas_ruangan, ["IC", "ICC", "NICU", "PICU"]);
            $kode    = $kelasIC ? ['K8#'] : ['K4#'];

            $rs = Rstigapuluhtarif::whereIn('rs3', $kode)
                ->where('rs4', 'like', "%|{$request->kdgroup_ruangan}|%")
                ->where('rs5', 'like', "%|{$request->kelas_ruangan}|%")
                ->first();
        }

        // return response()->json(['percobaan' => $rs]);

        // $rsx = collect($rs)->filter(function ($q) use ($request) {
        //     return Str::contains($q['rs5'], $request->kelas_ruangan) && Str::contains($q['rs4'], $request->kdgroup_ruangan);
        // })->first();

        // if (!$rsx) {
        //     return null;
        // }

        $rsx = $rs;

        // return response()->json(['percobaan222' => $rsx, $pegawai]);

        $sarana = 0;
        $pelayanan = 0;
        $flag_biaya = $rsx->rs3;

        $dokterRadiologi = $pegawai->profesi === 'J00113';
        $dokterPA = $pegawai->profesi === 'J00111';

        if ($dokterRadiologi || $dokterPA) {
            $sarana = 0;
            $pelayanan = 0;
        } else {

            if ($spesialis) {

                if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                } else if ($request->kelas_ruangan == "2") {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($request->kelas_ruangan == "1" || $request->kelas_ruangan == "ISO") {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($request->kelas_ruangan == "Utama") {
                    $sarana = $rsx->rs12;
                    $pelayanan = $rsx->rs13;
                } else if ($request->kelas_ruangan == "VIP") {
                    $sarana = $rsx->rs14;
                    $pelayanan = $rsx->rs15;
                } else if ($request->kelas_ruangan == "VVIP") {
                    $sarana = $rsx->rs16;
                    $pelayanan = $rsx->rs17;
                } else if ($request->kelas_ruangan == "HCU") {


                    $hakKelas = $request->hak_kelas;
                    if ($hakKelas === '1') {
                        $sarana = $rsx->rs10;
                        $pelayanan = $rsx->rs11;
                    } else if ($hakKelas === '2') {
                        $sarana = $rsx->rs8;
                        $pelayanan = $rsx->rs9;
                    } else if ($hakKelas === '3') {
                        $sarana = $rsx->rs6;
                        $pelayanan = $rsx->rs7;
                    }
                } else if ($request->kelas_ruangan == "PS") {

                    $sarana = $rsx->pss;
                    $pelayanan = $rsx->psp;
                }
            } else {
                if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                } else if ($request->kelas_ruangan === "2") {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($request->kelas_ruangan === "1" || $request->kelas_ruangan == "ISO") {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($request->kelas_ruangan === "Utama") {
                    $sarana = $rsx->rs12;
                    $pelayanan = $rsx->rs13;
                } else if ($request->kelas_ruangan === "VIP") {
                    $sarana = $rsx->rs14;
                    $pelayanan = $rsx->rs15;
                } else if ($request->kelas_ruangan === "VVIP") {
                    $sarana = $rsx->rs16;
                    $pelayanan = $rsx->rs17;
                } else if ($request->kelas_ruangan == "HCU") {


                    $hakKelas = $request->hak_kelas;
                    if ($hakKelas === '1') {
                        $sarana = $rsx->rs10;
                        $pelayanan = $rsx->rs11;
                    } else if ($hakKelas === '2') {
                        $sarana = $rsx->rs8;
                        $pelayanan = $rsx->rs9;
                    } else if ($hakKelas === '3') {
                        $sarana = $rsx->rs6;
                        $pelayanan = $rsx->rs7;
                    }
                } else if ($request->kelas_ruangan == "PS") {

                    $sarana = $rsx->pss;
                    $pelayanan = $rsx->psp;
                }
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


    public static function cekTaripVisite($spesialis, $request, $user)
    {
        
      $sarana=0;
      $pelayanan=0;
      $flag_biaya=null;





        if ($spesialis) {
          $rsx=null;
         


          $kelasIC = in_array($request->kelas_ruangan, ["IC", "ICC", "NICU"]);
          $status  = strtoupper($user->statusspesialis);

            if ($kelasIC) {
                $kode = $status === 'SPESIALIS' ? 'V3#' : ($status === 'SUB SPESIALIS' ? 'V7#' : null);
            } else {
                $kode = $status === 'SPESIALIS' ? 'V2#' : ($status === 'SUB SPESIALIS' ? 'V4#' : null);
            }

            $rsx = $kode
                ? Rstigapuluhtarif::where('rs3', $kode)
                    ->where('rs4', 'like', "%|{$request->kdgroup_ruangan}|%")
                    ->where('rs5', 'like', "%|{$request->kelas_ruangan}|%")
                    ->first()
                : null;
         
        
            // return $rsx;
          if (!$rsx) {
            $sarana=0;
            $pelayanan=0;
            $flag_biaya=null;
          }
          
          $flag_biaya=$rsx->rs3;

          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
          }else if($request->kelas_ruangan=="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan=="1" || $request->kelas_ruangan=="ISO"){
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
					}	else if ($request->kelas_ruangan == "HCU") {


            $hakKelas = $request->hak_kelas;
            if ($hakKelas === '1') {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if($hakKelas === '2'){
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if($hakKelas === '3'){
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            }
          } else if ($request->kelas_ruangan == "PS") {

              $sarana = $rsx->pss;
              $pelayanan = $rsx->psp;
              
          }
        } else {

          //select * from rs30tarif where (rs3='V1#

          $rsx=null;
          if ($request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" ){
            $rsx = Rstigapuluhtarif::where('rs3', 'V5#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          } else {
            $rsx = Rstigapuluhtarif::where('rs3', 'V1#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          }

          if (!$rsx) {
            return null;
          }

          
          $flag_biaya=$rsx->rs3;

          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
					}else if($request->kelas_ruangan==="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan==="1" || $request->kelas_ruangan=="ISO"){
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
					}	else if ($request->kelas_ruangan == "HCU") {


            $hakKelas = $request->hak_kelas;
            if ($hakKelas === '1') {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if($hakKelas === '2'){
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if($hakKelas === '3'){
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            }
          } else if ($request->kelas_ruangan == "PS") {

              $sarana = $rsx->pss;
              $pelayanan = $rsx->psp;
              
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

    public static function cekTarifKeperawatan( $kdRuang, $kelas)
    {
      $results = DB::table('rs30tarif')
        ->where('rs3', 'AK#')
        ->where('rs4', 'like', "%|{$kdRuang}|%")
        ->where('rs5', 'like', "%|{$kelas}|%")
        // ->where('rs2', 'like', "%{$term}%")
        ->orderBy('rs2')
        ->get();

      $data = [];

      foreach ($results as $rs) {
          $kode_biaya = $rs->rs1;
          $nama       = $rs->rs2;

          // Tentukan sarana & pelayanan sesuai kelas
          switch ($kelas) {
              case "3":
                  $sarana    = $rs->rs6;
                  $pelayanan = $rs->rs7;
                  break;
              case "2":
                  $sarana    = $rs->rs8;
                  $pelayanan = $rs->rs9;
                  break;
              case "1":
                  $sarana    = $rs->rs10;
                  $pelayanan = $rs->rs11;
                  break;
              case "Utama":
                  $sarana    = $rs->rs12;
                  $pelayanan = $rs->rs13;
                  break;
              case "VIP":
                  $sarana    = $rs->rs14;
                  $pelayanan = $rs->rs15;
                  break;
              case "VVIP":
                  $sarana    = $rs->rs16;
                  $pelayanan = $rs->rs17;
                  break;
              case "HCU":
                  $sarana    = $rs->hcus;
                  $pelayanan = $rs->hcup;
                  break;
              case "IC":
                  $sarana    = $rs->icus;
                  $pelayanan = $rs->icup;
                  break;
              case "ICC":
                  $sarana    = $rs->iccus;
                  $pelayanan = $rs->iccup;
                  break;
              case "NICU":
                  $sarana    = $rs->nicus;
                  $pelayanan = $rs->nicup;
                  break;
              case "IN":
                  $sarana    = $rs->ins;
                  $pelayanan = $rs->inp;
                  break;
              case "PS":
                  $sarana    = $rs->pss;
                  $pelayanan = $rs->psp;
                  break;
              case "ISO":
                  $sarana    = $rs->isos;
                  $pelayanan = $rs->isop;
                  break;
              default:
                  $sarana = $pelayanan = 0;
          }

          $data[] = [
              'label'      => $nama , // asumsi rp() helper format rupiah
              'kode_biaya' => $kode_biaya,
              'sarana'     => $sarana,
              'pelayanan'  => $pelayanan,
              'tarif'      => (int)$sarana + (int)$pelayanan,
              'value'      => $rs->rs2,
          ];
      }

      return $data;
    }
}
