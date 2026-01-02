<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\SuratKontrol;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\PsikiatriPoli;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuratKontrolController extends Controller
{

   public function index()
   {
      $data = DB::table('bpjs_surat_kontrol as bk')
         ->leftJoin('rs19 as p', 'bk.poliKontrol', '=', 'p.rs6')
         ->select('bk.*', 'p.rs2 as namaPoli')

         ->where('noreg', request('noreg'))
         ->get();

      return new JsonResponse($data, 200);
   }
   public function bpjsList()
   {
      $data = BridgingbpjsHelper::get_url(
         'vclaim',
         'RencanaKontrol/ListRencanaKontrol/Bulan/12/Tahun/2025/Nokartu/0002814619746/filter/2',
      );

      return new JsonResponse($data, 200);
   }

   public function update(Request $request, $noSuratKontrol)
   {
      $data = [
         'noSuratKontrol' => $noSuratKontrol,
         'request' => $request->all()
      ];

      return new JsonResponse($data, 200);
   }
   public function create(Request $request)
   {

      $user = auth()->user()->nama;
      $form = [
         "request" => [
            "noSEP"             => $request->noSEP,
            "kodeDokter"        => $request->kodeDokter,
            "poliKontrol"       => $request->poliKontrol,
            "tglRencanaKontrol" => $request->tglRencanaKontrol,
            "user"              => $user,
         ]
      ];

      $tgltobpjshttpres = DateHelper::getDateTime();

      $createSuratKontrol = BridgingbpjsHelper::post_url(
         'vclaim',
         'RencanaKontrol/insert',
         $form
      );

      // return new JsonResponse($createSuratKontrol, 200);



      $res = null;
      $noSuratKontrolResponse = null;
      $bpjs = $createSuratKontrol['metadata']['code'];
      if ($bpjs === 200 || $bpjs === '200') {
         // $createSuratKontrol = json_decode($createSuratKontrol, true); // ← penting!
         $noSuratKontrolResponse = data_get($createSuratKontrol, 'response.noSuratKontrol', '');

         $form_bpjs_surat_kontrol = [
            'noSuratKontrol' => $noSuratKontrolResponse,
            'norm' => $request->norm,
            'noreg' => $request->noreg,
            'noSEP' => $request->noSEP,
            'kodeDokter' => $request->kodeDokter,
            'poliKontrol' => $request->poliKontrol,
            'tglRencanaKontrol' => data_get($createSuratKontrol, 'response.tglRencanaKontrol', ''),
            'namaDokter' => data_get($createSuratKontrol, 'response.namaDokter', ''),
            'noKartu' => data_get($createSuratKontrol, 'response.noKartu', ''),
            'nama' => data_get($createSuratKontrol, 'response.nama', ''),
            'kelamin' => data_get($createSuratKontrol, 'response.kelamin', ''),
            'tglLahir' => data_get($createSuratKontrol, 'response.tglLahir', ''),
            'user_id' => auth()->user()->pegawai_id,
            'created_at' => now(),
            'updated_at' => now()
         ];


         $res = DB::table('bpjs_surat_kontrol')->insert($form_bpjs_surat_kontrol);

         Bpjs_http_respon::create(
            [
               'noreg' => $request->noreg === null ? '' : $request->noreg,
               'method' => 'POST',
               'request' => $form,
               'respon' => $createSuratKontrol,
               'url' => '/RencanaKontrol/insert',
               'tgl' => $tgltobpjshttpres
            ]
         );
         $data = [
            'message' => 'Sukses',
            'result' => $res,

         ];
         $kodeResp = 200;
      } else {
         Bpjs_http_respon::create(
            [
               'noreg' => $request->noreg === null ? '' : $request->noreg,
               'method' => 'POST',
               'request' => $form,
               'respon' => $createSuratKontrol,
               'url' => '/RencanaKontrol/insert',
               'tgl' => $tgltobpjshttpres
            ]
         );
         $data = [
            'message' => 'GAGAL, Response BPJS : ' . $createSuratKontrol['metadata']['message'],
            'result' => $res,
         ];
         $kodeResp = 410;
      }


      return new JsonResponse($data, $kodeResp);
   }
   public function remove(Request $request)
   {

      $user = auth()->user()->nama;
      $data = [
         "request" => [
            "t_suratkontrol" => [
               "noSuratKontrol" => $request->noSuratKontrol,
               "user" => $user
            ]
         ]
      ];
      $hapus = BridgingbpjsHelper::delete_url(
         'vclaim',
         '/RencanaKontrol/Delete',
         $data
      );

      $tgltobpjshttpres = DateHelper::getDateTime();

      Bpjs_http_respon::create(
         [
            'method' => 'delete',
            'noreg' => $request->noreg === null ? '' : $request->noreg,
            'request' => $data,
            'respon' => $hapus,
            'url' => '/RencanaKontrol/Delete',
            'tgl' => $tgltobpjshttpres
         ]
      );



      DB::table('bpjs_surat_kontrol')
         ->where('noSuratKontrol', $request->noSuratKontrol)
         ->delete();

      return response()->json(['message' => 'Surat kontrol berhasil dihapus', 'result' => $hapus], 200);
   }
}
