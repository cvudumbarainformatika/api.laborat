<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Hutangpasien;
use App\Models\Simrs\Master\Mkamar;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rsjr;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RegistrasiRanapController extends Controller
{
    public function registrasiranap(Request $request)
    {
      if ($request->barulama === 'baru') {
        $data = Mpasien::where('rs1', $request->norm)->first();
        if ($data) {
            return new JsonResponse([
                'message' => 'Nomor RM Sudah ada',
                'data' => $data
            ], 410);
        }
        $data2 = Mpasien::where('rs49', $request->nik)->first();
        if ($data2) {
            return new JsonResponse([
                'message' => 'NIK Sudah didaftarkan',
                'data' => $data
            ], 410);
        }
      }

      $masterpasien = PendaftaranByForm::store($request);
      if (!$masterpasien) {
        return new JsonResponse(['message' => 'DATA MASTER PASIEN GAGAL DISIMPAN/DIUPDATE'], 500);
      }

      

      $cekRanap = Kunjunganranap::select('rs1')->where('rs2','=', $request->norm)->where('rs22','=', '')->get();
      $cekHutang = Hutangpasien::where('rs2', $request->norm)->whrere('rs18','1')->get();
      $cekIgd = KunjunganPoli::select('rs1')->where('rs2', $request->norm)->where('rs19', '')->where('rs8', 'POL014')->get();

      if (count($cekRanap) > 0) {
        return new JsonResponse(['message' => 'Maaf, pasien tersebut sudah rawat inap'], 500);
      }
      if (count($cekHutang) > 0) {
        return new JsonResponse(['message' => 'Maaf, Pasien Ini Masih Mempunyai Hutang Di RSUD dr. Mohamad Saleh...!!!'], 500);
      }
      if (count($cekIgd) > 0) {
        return new JsonResponse(['message' => 'Maaf, kondisi akhir di igd belum dientri. segera hubungi admin igd.'], 500);
      }

      //NOREG jk kosong ambil dari counter

      DB::select('call reg_ranap(@nomor)');
      $hcounter = DB::table('rs1')->select('rs12')->get();
      $wew = $hcounter[0]->rs12;
      $noreg = FormatingHelper::gennoreg($wew, 'I');

      $input = new Request([
          'noreg' => $noreg
      ]);

      $input->validate([
          'noreg' => 'required|unique:rs23,rs1'
      ]);

      $tglMsk = $request->tglmasuk ?? Carbon::now()->format('Y-m-d');
      $tglmasuk = Carbon::create($tglMsk)->toDateString();

      // INI DARI IGD

      $tempNoreg = null;

      $ruang = $request->ruang ?? '';
      $kamar = $request->kamar ?? ''; // belum ada di request
      $noBed = $request->no_bed ?? ''; // belum ada di request

      $titipan = $request->titipan ?? ''; // masih ada di request

      if ($request->has('noreg')) {
        $reg = Kunjunganranap::updateOrCreate(
          ['rs1' => $request->noreg],
          [
            'rs13' => $request->asalrujukan ?? '',
            'rs5' => $ruang,
            'rs6' => $kamar,
            'rs7' => $noBed,
            'rs10'=> $request->kd_dokter ?? '',
            'rs19'=> $request->kodesistembayar ?? '',
            'rs11'=> $request->penanggungjawab ?? '',
            'rs30'=> auth()->user()->pegawai_id ?? '',
            'rs31'=> '',
            'rs38'=> $request->hakKelasBpjs ?? '', // hak Kelas dari BPJS
            'rs39'=> $request->diagnosaAwal ?? '', // ICD
            'rs40'=> $request->diagnosa ?? '', // BELUM ADA di request
            'titipan' => $titipan
          ]
        );

        $tempNoreg = $request->noreg;
          
      } else { // INI DARI SELAIN IGD (bisa dr poli atau lain-lain SPRI)

        $reg = new Kunjunganranap();
        $reg->rs1 = $input->noreg;
        $reg->rs2 = $request->norm;
        $reg->rs3 = $tglmasuk;
        $reg->rs13 = $request->asalrujukan ?? '';
        $reg->rs5 = $ruang;
        $reg->rs6 = $kamar;
        $reg->rs7 = $noBed;
        $reg->rs10 = $request->kd_dokter ?? '';
        $reg->rs19 = $request->kodesistembayar ?? '';
        $reg->rs11 = $request->penanggungjawab ?? '';
        $reg->rs30 = auth()->user()->pegawai_id ?? '';
        $reg->rs31 = '';
        $reg->rs38 = $request->hakKelasBpjs ?? '';
        $reg->rs39 = $request->diagnosaAwal ?? '';
        $reg->rs40 = $request->diagnosa ?? '';
        $reg->titipan = $request->titipan ?? '';
        $reg->save();

        if ($request->kodesistembayar === 'AR32') {
          Rsjr::updateOrCreate(
            ['rs1' => $reg->noreg],
            [
              'rs2' => $request->norm, 'rs3'=> 'JR1',
              'rs4' => 'Pembuatan Dokumen Asuransi',
              'rs5' => '45000', 'rs6' => auth()->user()->pegawai_id,
            ]
          );
        }

        $tempNoreg = $reg->noreg;

      }

      // UPDATE rs25
      if ($request->titpan !== null || $request->titpan !== '') {
        $ruangan = $titipan === '' ? $request->ruang : $titipan;
        $rs24 = Mkamar::where('rs1', $ruangan)->first();
        if ($rs24) {
          $rs25 =MkamarRanap::where('rs5', $ruangan)->where('rs1', $kamar)->where('rs2', $noBed)->first();
          if ($rs25) {
            $rs25->rs3 = 'S';
            $rs25->rs4 = 'N';
            $rs25->save();
          }
          $rs25NonKelas = MkamarRanap::where('rs6', $rs24->groups)->where('rs1', $kamar)->where('rs2', $noBed)->where('rs5','-')->first();
          if ($rs25NonKelas) {
            $rs25NonKelas->rs3 = 'S';
            $rs25NonKelas->rs4 = 'N';
            $rs25NonKelas->save();
          }
        } 
      }


      return new JsonResponse(['message' => 'ok'], 200);

    }



}
