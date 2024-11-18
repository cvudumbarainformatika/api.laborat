<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\DischargePlanning\DischargePlanning;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\SuratPasien\SuratPasien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PulangController extends Controller
{
    
    public function getmastercarakeluar()
    {
        $data = DB::table('rs26')->select('rs1','rs2')->get();
        return new JsonResponse($data);
    }
    public function simpandata(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      //  return $anamnesis;
      $sqlz = DB::table('cppts')->select('*')->where('noreg', $request->noreg)->get();
      $sqla = DB::table('rs242')->select('*')->where('rs1', $request->noreg)->get();
      $inStatus = ['2'.'3'];
      $sql_cek_kunjungan = DB::table('rs23')->select('*')->where('rs1', $request->noreg)->whereIn('rs22', $inStatus)->get();

      $rencana = count($sqla);
      $cppt = count($sqlz);
      $kunjungan = count($sql_cek_kunjungan);

      if ($kunjungan > 0) {
        return new JsonResponse([
          'message' => 'Maaf, Pasien telah pulang'
        ], 500);
      }

      if($rencana === 0 && $cppt === 0){
        return new JsonResponse([
          'message' => 'Maaf, Rencana Tindak Lanjut Pasien dan Perkembangan Pasien harus di isi.'
        ], 500);
      }

      $meninggal = $request->caraKeluar === 'C003';
      $plgPaksa = $request->caraKeluar === 'C010';
      $sistemByrBpjs = ($request->kodesistembayar === 'BPJS' || $request->kodesistembayar === 'BPJS1' || $request->kodesistembayar === 'BPJS2' || $request->kodesistembayar === 'BPJS3' || $request->kodesistembayar === 'BPJS4' || $request->kodesistembayar==='BPJS5');

      if ($plgPaksa && $sistemByrBpjs) {
        Pasien::where('rs1', $request->norm)->where('rs1', $request->norm)->update([
          'rs53' => '1',
        ]);
      } 

      $kunjunganRanap = Kunjunganranap::where('rs1', $request->noreg)->first();
      $updateKunjunganRanap = $kunjunganRanap->update([
        'rs22' => '3',
        'rs4' => date('Y-m-d H:i:s'),
        'rs23' => $request->caraKeluar ?? '',
        'rs24' => $request->prognosis ?? '',
        'rs26' => $request->diagnosaAkhir ?? '',
        'rs25' => $request->diagnosaPenyebabMeninggal ?? '',
        'rs27' => $request->tindakLanjut ?? ''
      ]);

       if (!$updateKunjunganRanap) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       if ($request->noSuratMeninggal !== null || $request->noLp !== null) {
          SuratPasien::updateOrCreate(
            ['noreg' => $request->noreg],
            [
              'nosuratmeninggal' => $request->noSuratMeninggal,
              'nolp' => $request->noLp
            ]
          );
       }

       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $sql_cek_kunjungan
       ]);
    }


   public function hapusdata(Request $request)
   {
       $cari = DischargePlanning::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }


    
}
